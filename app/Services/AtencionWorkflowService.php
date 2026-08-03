<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\DetalleTecnico;
use App\Models\Pago;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtencionWorkflowService
{
    public function decidir(Cita $cita, string $decision, ?string $motivo = null): Cita
    {
        return DB::transaction(function () use ($cita, $decision, $motivo) {
            $cita->loadMissing('detalleTecnico');

            if ($decision !== 'pendiente' && blank($cita->detalleTecnico?->diagnostico)) {
                throw ValidationException::withMessages([
                    'diagnostico' => 'Registra el diagnóstico técnico antes de guardar la decisión.',
                ]);
            }

            if ($decision !== 'pendiente' && blank($cita->propuesta)) {
                throw ValidationException::withMessages([
                    'propuesta' => 'Registra la propuesta presentada al cliente.',
                ]);
            }

            if ($decision === 'rechazado' && blank($motivo)) {
                throw ValidationException::withMessages([
                    'motivo_rechazo' => 'Indica por qué el cliente rechazó la propuesta.',
                ]);
            }

            $aceptado = $decision === 'aceptado';
            $rechazado = $decision === 'rechazado';

            $cita->update([
                'decision_cliente' => $decision,
                'motivo_rechazo' => $rechazado ? $motivo : null,
                'decision_at' => $decision === 'pendiente' ? null : now(),
                'cerrado_at' => $rechazado ? now() : null,
                'etapa' => $aceptado ? 'servicio' : ($rechazado ? 'cerrada' : 'propuesta'),
                'estado' => $aceptado ? 'en_proceso' : ($rechazado ? 'cancelada' : 'revision'),
            ]);

            return $cita->fresh();
        });
    }

    public function sincronizarDesdeCita(Cita $cita): void
    {
        if ($cita->decision_cliente === 'rechazado') {
            $cita->forceFill(['etapa' => 'cerrada', 'estado' => 'cancelada'])->save();

            return;
        }

        if ($cita->decision_cliente === 'aceptado') {
            return;
        }

        $etapa = filled($cita->propuesta)
            ? 'propuesta'
            : ($cita->detalleTecnico?->diagnostico ? 'diagnostico' : 'cita');

        if ($cita->etapa !== $etapa) {
            $cita->forceFill([
                'etapa' => $etapa,
                'estado' => $etapa === 'cita' ? 'pendiente' : 'revision',
            ])->save();
        }
    }

    public function sincronizarDesdeDetalle(DetalleTecnico $detalle): void
    {
        $cita = $detalle->cita;
        if (!$cita) {
            return;
        }

        $items = collect($detalle->items ?? []);
        $hayTrabajo = filled($detalle->trabajo_realizado)
            || filled($detalle->repuestos);

        if ($hayTrabajo && $cita->decision_cliente !== 'aceptado') {
            throw ValidationException::withMessages([
                'decision_cliente' => 'Primero registra que el cliente aceptó la propuesta.',
            ]);
        }

        $manoObra = $items
            ->filter(fn ($item) => ($item['tipo'] ?? 'mano_obra') !== 'material')
            ->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
        $materiales = $items
            ->filter(fn ($item) => ($item['tipo'] ?? null) === 'material')
            ->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));

        $cambios = [];
        if ($items->isNotEmpty()) {
            $cambios['costo_mano_obra'] = round($manoObra, 2);
            $cambios['costo_materiales'] = round($materiales, 2);
            $cambios['total'] = max(round($manoObra + $materiales - (float) $cita->descuento, 2), 0);
        }

        if ($cita->decision_cliente === 'aceptado') {
            $totalActualizado = (float) ($cambios['total'] ?? $cita->total);
            $pagado = (float) $cita->pagos()->where('estado', 'pagado')->sum('monto');
            $pagoCompleto = $totalActualizado > 0 && $pagado >= $totalActualizado;
            $servicioFinalizado = in_array($cita->estado, ['terminado', 'entregado', 'concluida'], true)
                || in_array($cita->etapa, ['pago', 'garantia'], true);

            if ($servicioFinalizado) {
                $cambios['etapa'] = $pagoCompleto && (int) $detalle->garantia_dias > 0
                    ? 'garantia'
                    : 'pago';
            } else {
                $cambios['etapa'] = 'servicio';
                $cambios['estado'] = $hayTrabajo ? 'en_proceso' : $cita->estado;
            }
        } elseif (filled($detalle->diagnostico)) {
            $cambios['etapa'] = filled($cita->propuesta) ? 'propuesta' : 'diagnostico';
            $cambios['estado'] = 'revision';
        }

        if (!empty($cambios)) {
            $cita->forceFill($cambios)->save();
        }
    }

    public function finalizarServicio(Cita $cita): Cita
    {
        if ($cita->decision_cliente !== 'aceptado') {
            throw ValidationException::withMessages([
                'decision_cliente' => 'Solo se puede finalizar un servicio aceptado por el cliente.',
            ]);
        }

        $cita->loadMissing('detalleTecnico');
        if (blank($cita->detalleTecnico?->trabajo_realizado)) {
            throw ValidationException::withMessages([
                'trabajo_realizado' => 'Registra el trabajo realizado antes de finalizar el servicio.',
            ]);
        }

        $cita->update(['estado' => 'terminado', 'etapa' => 'pago']);

        return $cita->fresh();
    }

    public function sincronizarDesdePago(Pago $pago): void
    {
        $cita = $pago->cita;
        if (!$cita) {
            return;
        }

        if ($cita->decision_cliente !== 'aceptado') {
            throw ValidationException::withMessages([
                'decision_cliente' => 'No se puede cobrar un servicio que el cliente no aceptó.',
            ]);
        }

        if (!in_array($cita->estado, ['terminado', 'entregado', 'concluida'], true)
            && !in_array($cita->etapa, ['pago', 'garantia'], true)) {
            throw ValidationException::withMessages([
                'estado' => 'Finaliza primero el servicio antes de registrar el pago.',
            ]);
        }

        $pagado = (float) $cita->pagos()->where('estado', 'pagado')->sum('monto');
        $total = (float) $cita->total;
        $garantia = $cita->detalleTecnico;
        $pagoCompleto = $total > 0 && $pagado >= $total;
        $tieneGarantia = $garantia && (int) $garantia->garantia_dias > 0;

        $cita->forceFill([
            'etapa' => $pagoCompleto && $tieneGarantia ? 'garantia' : 'pago',
            'estado' => $pagoCompleto ? 'entregado' : $cita->estado,
        ])->save();
    }

    public function prepararGarantia(array $datos): array
    {
        $dias = (int) ($datos['garantia_dias'] ?? 0);
        $inicio = $datos['garantia_inicio'] ?? $datos['fecha_entrega'] ?? null;

        if ($dias > 0 && $inicio) {
            $datos['garantia_inicio'] = $inicio;
            $datos['garantia_fin'] = Carbon::parse($inicio)->addDays($dias)->toDateString();
            $datos['garantia'] = ($datos['garantia'] ?? null)
                ?: "{$dias} días sobre el trabajo realizado";
        } else {
            $datos['garantia_dias'] = 0;
            $datos['garantia_inicio'] = null;
            $datos['garantia_fin'] = null;
        }

        return $datos;
    }
}
