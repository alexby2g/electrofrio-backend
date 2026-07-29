<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\DetalleTecnico;
use App\Models\Equipo;
use App\Models\Pago;
use App\Models\Servicio;
use App\Models\Tecnico;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $inicioAnio = Carbon::now()->startOfYear();
        $finAnio = Carbon::now()->endOfYear();
        $inicioMesAnterior = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior = Carbon::now()->subMonthNoOverflow()->endOfMonth();

        $estados = Cita::select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $citas = Cita::with(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico'])
            ->latest()
            ->get();

        $pagosConfirmados = Pago::with(['cliente', 'cita.servicio', 'cita.equipo'])
            ->where('estado', 'pagado')
            ->get();

        $trabajosAbiertos = $citas->whereNotIn('estado', ['entregado', 'concluida', 'cancelada'])->count();
        $pendienteCobro = $citas->where('estado', '!=', 'cancelada')->sum(function ($cita) {
            $pagado = $cita->pagos->where('estado', 'pagado')->sum('monto');
            return max((float) $cita->total - (float) $pagado, 0);
        });

        $ingresosTotal = (float) $pagosConfirmados->sum('monto');
        $ingresosHoy = (float) $pagosConfirmados
            ->filter(fn ($pago) => $pago->fecha_pago?->isSameDay($hoy))
            ->sum('monto');
        $ingresosMes = (float) $pagosConfirmados
            ->filter(fn ($pago) => $pago->fecha_pago?->betweenIncluded($inicioMes, $finMes))
            ->sum('monto');
        $ingresosMesAnterior = (float) $pagosConfirmados
            ->filter(fn ($pago) => $pago->fecha_pago?->betweenIncluded($inicioMesAnterior, $finMesAnterior))
            ->sum('monto');
        $ingresosAnio = (float) $pagosConfirmados
            ->filter(fn ($pago) => $pago->fecha_pago?->betweenIncluded($inicioAnio, $finAnio))
            ->sum('monto');

        $variacionMensual = $ingresosMesAnterior > 0
            ? round((($ingresosMes - $ingresosMesAnterior) / $ingresosMesAnterior) * 100, 1)
            : null;

        $ingresosMensuales = collect(range(5, 0))->map(function ($mesesAtras) use ($pagosConfirmados) {
            $mes = Carbon::now()->subMonthsNoOverflow($mesesAtras);
            $inicio = $mes->copy()->startOfMonth();
            $fin = $mes->copy()->endOfMonth();

            return [
                'clave' => $mes->format('Y-m'),
                'mes' => ucfirst($mes->locale('es')->translatedFormat('M')),
                'anio' => $mes->year,
                'total' => round((float) $pagosConfirmados
                    ->filter(fn ($pago) => $pago->fecha_pago?->betweenIncluded($inicio, $fin))
                    ->sum('monto'), 2),
            ];
        })->values();

        $metodosPago = $pagosConfirmados
            ->groupBy('metodo_pago')
            ->map(fn ($pagos, $metodo) => [
                'metodo' => $metodo,
                'cantidad' => $pagos->count(),
                'total' => round((float) $pagos->sum('monto'), 2),
            ])
            ->sortByDesc('total')
            ->values();

        $rendimientoTecnicos = $citas
            ->whereNotNull('tecnico_id')
            ->groupBy('tecnico_id')
            ->map(function ($trabajos) {
                $terminados = $trabajos->whereIn('estado', ['terminado', 'entregado', 'concluida'])->count();

                return [
                    'tecnico_id' => $trabajos->first()->tecnico_id,
                    'tecnico' => $trabajos->first()->tecnico?->nombre,
                    'total_trabajos' => $trabajos->count(),
                    'terminados' => $terminados,
                    'en_curso' => $trabajos->whereIn('estado', ['revision', 'en_proceso', 'esperando_repuesto'])->count(),
                    'facturado' => round((float) $trabajos->whereIn('estado', ['terminado', 'entregado', 'concluida'])->sum('total'), 2),
                    'efectividad' => $trabajos->count() > 0
                        ? round(($terminados / $trabajos->count()) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('terminados')
            ->take(5)
            ->values();

        $atencionesAtrasadas = $citas
            ->whereNotIn('estado', ['terminado', 'entregado', 'concluida', 'cancelada'])
            ->filter(fn ($cita) => $cita->fecha?->lt($hoy))
            ->sortBy('fecha')
            ->values();

        $garantias = DetalleTecnico::with(['cita.cliente', 'cita.equipo', 'cita.servicio'])
            ->whereNotNull('garantia')
            ->whereNotNull('fecha_entrega')
            ->get()
            ->map(function ($detalle) use ($hoy) {
                $dias = $this->extraerDiasGarantia($detalle->garantia);
                if (!$dias) {
                    return null;
                }

                $vence = Carbon::parse($detalle->fecha_entrega)->addDays($dias);

                return [
                    'detalle_id' => $detalle->id,
                    'cita_id' => $detalle->cita_id,
                    'cliente' => $detalle->cita?->cliente?->nombre,
                    'equipo' => trim(collect([
                        $detalle->cita?->equipo?->tipo,
                        $detalle->cita?->equipo?->marca,
                        $detalle->cita?->equipo?->modelo,
                    ])->filter()->join(' · ')),
                    'servicio' => $detalle->cita?->servicio?->nombre,
                    'garantia' => $detalle->garantia,
                    'fecha_entrega' => optional($detalle->fecha_entrega)->format('Y-m-d'),
                    'vence' => $vence->format('Y-m-d'),
                    'dias_restantes' => $hoy->diffInDays($vence, false),
                    'estado' => $vence->endOfDay()->gte($hoy) ? 'vigente' : 'vencida',
                ];
            })
            ->filter()
            ->values();

        $garantiasVigentes = $garantias->where('estado', 'vigente')->values();
        $garantiasPorVencer = $garantiasVigentes->filter(fn ($garantia) => $garantia['dias_restantes'] <= 7)->values();

        return response()->json([
            'mensaje' => 'Dashboard profesional de Electro Frío',
            'totales' => [
                'clientes' => Cliente::count(),
                'tecnicos' => Tecnico::count(),
                'equipos' => Equipo::count(),
                'servicios' => Servicio::count(),
                'citas' => Cita::count(),
                'pagos' => round($ingresosTotal, 2),
            ],
            'operacion_diaria' => [
                'atenciones_hoy' => Cita::whereDate('fecha', $hoy)->count(),
                'ingresos_hoy' => round($ingresosHoy, 2),
                'ingresos_mes' => round($ingresosMes, 2),
                'trabajos_abiertos' => $trabajosAbiertos,
                'pendiente_cobro' => round($pendienteCobro, 2),
                'garantias_vigentes' => $garantiasVigentes->count(),
                'garantias_por_vencer' => $garantiasPorVencer->count(),
                'atenciones_atrasadas' => $atencionesAtrasadas->count(),
            ],
            'finanzas' => [
                'ingresos_total' => round($ingresosTotal, 2),
                'ingresos_anio' => round($ingresosAnio, 2),
                'ingresos_mes' => round($ingresosMes, 2),
                'ingresos_mes_anterior' => round($ingresosMesAnterior, 2),
                'ingresos_hoy' => round($ingresosHoy, 2),
                'pendiente_cobro' => round($pendienteCobro, 2),
                'variacion_mensual' => $variacionMensual,
            ],
            'ingresos_mensuales' => $ingresosMensuales,
            'metodos_pago' => $metodosPago,
            'rendimiento_tecnicos' => $rendimientoTecnicos,
            'estado_citas' => [
                'pendiente' => (int) ($estados['pendiente'] ?? 0),
                'revision' => (int) ($estados['revision'] ?? 0),
                'en_proceso' => (int) ($estados['en_proceso'] ?? 0),
                'esperando_repuesto' => (int) ($estados['esperando_repuesto'] ?? 0),
                'terminado' => (int) (($estados['terminado'] ?? 0) + ($estados['concluida'] ?? 0)),
                'entregado' => (int) ($estados['entregado'] ?? 0),
                'cancelada' => (int) ($estados['cancelada'] ?? 0),
            ],
            'servicios_top' => Cita::select('servicio_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(total) as ingresos'))
                ->with('servicio')
                ->whereNotNull('servicio_id')
                ->groupBy('servicio_id')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'garantias_por_vencer_lista' => $garantiasPorVencer->take(5)->values(),
            'atenciones_atrasadas_lista' => $atencionesAtrasadas->take(6)->values(),
            'ultimas_citas' => Cita::with(['cliente', 'tecnico', 'servicio', 'equipo'])
                ->orderByDesc('fecha')
                ->orderByDesc('hora')
                ->take(8)
                ->get(),
            'ultimos_pagos' => Pago::with(['cliente', 'cita.servicio', 'cita.equipo'])->latest()->take(6)->get(),
        ]);
    }

    private function extraerDiasGarantia(?string $garantia): ?int
    {
        if (!$garantia) {
            return null;
        }

        if (preg_match('/(\d+)\s*(d[ií]a|dias|días)/i', $garantia, $coincidencias)) {
            return (int) $coincidencias[1];
        }

        if (preg_match('/(\d+)\s*(mes|meses)/i', $garantia, $coincidencias)) {
            return (int) $coincidencias[1] * 30;
        }

        return null;
    }
}
