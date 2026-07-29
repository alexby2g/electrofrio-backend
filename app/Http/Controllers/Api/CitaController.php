<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CitaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->query('buscar');
        $estado = $request->query('estado');

        $citas = Cita::with(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico.tecnico'])
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($searchQuery) use ($buscar) {
                    $searchQuery->where('descripcion', 'like', "%{$buscar}%")
                        ->orWhere('observacion', 'like', "%{$buscar}%")
                        ->orWhereHas('cliente', function ($clienteQuery) use ($buscar) {
                            $clienteQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('tecnico', function ($tecnicoQuery) use ($buscar) {
                            $tecnicoQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('servicio', function ($servicioQuery) use ($buscar) {
                            $servicioQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('equipo', function ($equipoQuery) use ($buscar) {
                            $equipoQuery->where('tipo', 'like', "%{$buscar}%")
                                ->orWhere('marca', 'like', "%{$buscar}%")
                                ->orWhere('modelo', 'like', "%{$buscar}%")
                                ->orWhere('serie', 'like', "%{$buscar}%")
                                ->orWhere('ubicacion', 'like', "%{$buscar}%");
                        });
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get();

        return response()->json(['mensaje' => 'Listado de citas', 'data' => $citas]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $this->validarEquipoDelCliente($datos);

        $cita = Cita::create($datos)->load(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico.tecnico']);
        $this->prepararEvidenciasParaMostrar($cita);

        return response()->json(['mensaje' => 'Cita registrada correctamente', 'data' => $cita], 201);
    }

    public function show(Cita $cita)
    {
        return response()->json([
            'mensaje' => 'Detalle de la cita',
            'data' => $this->prepararEvidenciasParaMostrar($cita->load(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico.tecnico'])),
        ]);
    }

    public function update(Request $request, Cita $cita)
    {
        $datos = $this->validar($request);
        $this->validarEquipoDelCliente($datos);
        $cita->update($datos);

        return response()->json(['mensaje' => 'Cita actualizada correctamente', 'data' => $this->prepararEvidenciasParaMostrar($cita->load(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico.tecnico']))]);
    }

    public function destroy(Cita $cita)
    {
        $cita->delete();

        return response()->json(['mensaje' => 'Cita eliminada correctamente']);
    }


    public function documento(Cita $cita)
    {
        $cita->load(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico.tecnico']);
        $this->prepararEvidenciasParaMostrar($cita);

        $total = (float) ($cita->total ?? 0);
        $montoPagado = (float) $cita->pagos
            ->where('estado', 'pagado')
            ->sum('monto');
        $saldoPendiente = max($total - $montoPagado, 0);

        return response()->json([
            'mensaje' => 'Datos listos para generar documento',
            'data' => [
                'cita' => $cita,
                'resumen' => [
                    'total' => round($total, 2),
                    'monto_pagado' => round($montoPagado, 2),
                    'saldo_pendiente' => round($saldoPendiente, 2),
                    'estado_pago' => $saldoPendiente <= 0 && $total > 0 ? 'pagado' : 'pendiente',
                ],
            ],
        ]);
    }

    public function finalizar(Cita $cita)
    {
        $cita->update(['estado' => 'terminado']);

        return response()->json(['mensaje' => 'Atención marcada como terminada correctamente', 'data' => $cita->fresh()->load(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico.tecnico'])]);
    }

    public function cambiarEstado(Request $request, Cita $cita)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in($this->estadosPermitidos())],
        ]);

        $cita->update(['estado' => $datos['estado']]);

        return response()->json([
            'mensaje' => 'Estado actualizado correctamente',
            'data' => $this->prepararEvidenciasParaMostrar($cita->fresh()->load(['cliente', 'tecnico', 'servicio', 'equipo', 'pagos', 'detalleTecnico.tecnico'])),
        ]);
    }


    private function estadosPermitidos(): array
    {
        return [
            'pendiente',
            'revision',
            'en_proceso',
            'esperando_repuesto',
            'terminado',
            'entregado',
            'concluida', // estado anterior, mantenido para no romper datos existentes
            'cancelada',
        ];
    }

    private function prepararEvidenciasParaMostrar(Cita $cita): Cita
    {
        if ($cita->relationLoaded('detalleTecnico') && $cita->detalleTecnico) {
            $cita->detalleTecnico->aplicarEvidenciasConDataUrl();
        }

        return $cita;
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tecnico_id' => 'nullable|exists:tecnicos,id',
            'servicio_id' => 'nullable|exists:servicios,id',
            'equipo_id' => 'nullable|exists:equipos,id',
            'fecha' => 'required|date',
            'hora' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'estado' => ['nullable', Rule::in($this->estadosPermitidos())],
            'descripcion' => 'nullable|string',
            'total' => 'nullable|numeric|min:0',
            'observacion' => 'nullable|string',
        ]);
    }

    private function validarEquipoDelCliente(array $datos): void
    {
        if (empty($datos['equipo_id'])) {
            return;
        }

        $equipo = Equipo::find($datos['equipo_id']);

        if ($equipo && (int) $equipo->cliente_id !== (int) $datos['cliente_id']) {
            throw ValidationException::withMessages([
                'equipo_id' => 'El equipo seleccionado no pertenece al cliente indicado.',
            ]);
        }
    }
}
