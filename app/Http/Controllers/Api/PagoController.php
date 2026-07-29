<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->query('buscar');

        $pagos = Pago::with(['cliente', 'cita.servicio', 'cita.equipo'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('metodo_pago', 'like', "%{$buscar}%")
                    ->orWhere('estado', 'like', "%{$buscar}%")
                    ->orWhereHas('cliente', function ($clienteQuery) use ($buscar) {
                        $clienteQuery->where('nombre', 'like', "%{$buscar}%");
                    });
            })
            ->latest()
            ->get();

        return response()->json(['mensaje' => 'Listado de pagos', 'data' => $pagos]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        if (!empty($datos['cita_id']) && empty($datos['cliente_id'])) {
            $cita = Cita::find($datos['cita_id']);
            $datos['cliente_id'] = $cita?->cliente_id;
        }

        if (empty($datos['fecha_pago'])) {
            $datos['fecha_pago'] = now()->toDateString();
        }

        $pago = Pago::create($datos)->load(['cliente', 'cita.servicio', 'cita.equipo']);

        return response()->json(['mensaje' => 'Pago registrado correctamente', 'data' => $pago], 201);
    }

    public function show(Pago $pago)
    {
        return response()->json(['mensaje' => 'Detalle del pago', 'data' => $pago->load(['cliente', 'cita.servicio', 'cita.equipo'])]);
    }

    public function update(Request $request, Pago $pago)
    {
        $datos = $this->validar($request);

        if (!empty($datos['cita_id']) && empty($datos['cliente_id'])) {
            $cita = Cita::find($datos['cita_id']);
            $datos['cliente_id'] = $cita?->cliente_id;
        }

        $pago->update($datos);

        return response()->json(['mensaje' => 'Pago actualizado correctamente', 'data' => $pago->load(['cliente', 'cita.servicio', 'cita.equipo'])]);
    }

    public function destroy(Pago $pago)
    {
        $pago->delete();

        return response()->json(['mensaje' => 'Pago eliminado correctamente']);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'cita_id' => 'nullable|exists:citas,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'monto' => 'required|numeric|min:0',
            'metodo_pago' => ['required', Rule::in(['efectivo', 'qr', 'transferencia', 'mixto'])],
            'estado' => ['nullable', Rule::in(['pendiente', 'pagado', 'anulado'])],
            'fecha_pago' => 'nullable|date',
            'observacion' => 'nullable|string',
        ]);
    }
}
