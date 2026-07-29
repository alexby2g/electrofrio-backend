<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->query('buscar');

        $clientes = Cliente::query()
            ->withCount(['equipos', 'citas'])
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('telefono', 'like', "%{$buscar}%")
                    ->orWhere('direccion', 'like', "%{$buscar}%")
                    ->orWhere('equipo', 'like', "%{$buscar}%")
                    ->orWhere('marca', 'like', "%{$buscar}%");
            })
            ->latest()
            ->get();

        return response()->json([
            'mensaje' => 'Listado de clientes',
            'data' => $clientes,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'equipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'observacion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        $cliente = Cliente::create($datos);

        return response()->json([
            'mensaje' => 'Cliente registrado correctamente',
            'data' => $cliente,
        ], 201);
    }

    public function show(Cliente $cliente)
    {
        return response()->json([
            'mensaje' => 'Detalle del cliente',
            'data' => $cliente->load([
                'equipos',
                'citas.equipo',
                'citas.servicio',
                'citas.tecnico',
                'pagos.cita',
            ]),
        ]);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'equipo' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'observacion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        $cliente->update($datos);

        return response()->json([
            'mensaje' => 'Cliente actualizado correctamente',
            'data' => $cliente,
        ]);
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->json([
            'mensaje' => 'Cliente eliminado correctamente',
        ]);
    }

    public function resumen()
    {
        return response()->json([
            'mensaje' => 'Resumen general de clientes de Electro Frío',
            'total_clientes' => Cliente::count(),
            'clientes_activos' => Cliente::where('activo', true)->count(),
            'ultimos_clientes' => Cliente::latest()->take(5)->get(),
        ]);
    }
}
