<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->query('buscar');

        $equipos = Equipo::with('cliente')->withCount('citas')
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('tipo', 'like', "%{$buscar}%")
                    ->orWhere('marca', 'like', "%{$buscar}%")
                    ->orWhere('modelo', 'like', "%{$buscar}%")
                    ->orWhere('serie', 'like', "%{$buscar}%")
                    ->orWhere('ubicacion', 'like', "%{$buscar}%")
                    ->orWhereHas('cliente', function ($clienteQuery) use ($buscar) {
                        $clienteQuery->where('nombre', 'like', "%{$buscar}%");
                    });
            })
            ->latest()
            ->get();

        return response()->json(['mensaje' => 'Listado de equipos', 'data' => $equipos]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'tipo' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'observacion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        $equipo = Equipo::create($datos)->load('cliente');

        return response()->json(['mensaje' => 'Equipo registrado correctamente', 'data' => $equipo], 201);
    }

    public function show(Equipo $equipo)
    {
        return response()->json(['mensaje' => 'Detalle del equipo', 'data' => $equipo->load(['cliente', 'citas.cliente', 'citas.tecnico', 'citas.servicio', 'citas.detalleTecnico', 'citas.pagos'])]);
    }

    public function update(Request $request, Equipo $equipo)
    {
        $datos = $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'tipo' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'observacion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        $equipo->update($datos);

        return response()->json(['mensaje' => 'Equipo actualizado correctamente', 'data' => $equipo->load('cliente')]);
    }

    public function destroy(Equipo $equipo)
    {
        $equipo->delete();

        return response()->json(['mensaje' => 'Equipo eliminado correctamente']);
    }
}
