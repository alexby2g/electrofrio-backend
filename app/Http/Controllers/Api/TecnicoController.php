<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->query('buscar');

        $tecnicos = Tecnico::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('telefono', 'like', "%{$buscar}%")
                    ->orWhere('especialidad', 'like', "%{$buscar}%");
            })
            ->latest()
            ->get();

        return response()->json(['mensaje' => 'Listado de técnicos', 'data' => $tecnicos]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'especialidad' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $tecnico = Tecnico::create($datos);

        return response()->json(['mensaje' => 'Técnico registrado correctamente', 'data' => $tecnico], 201);
    }

    public function show(Tecnico $tecnico)
    {
        return response()->json(['mensaje' => 'Detalle del técnico', 'data' => $tecnico->load('citas')]);
    }

    public function update(Request $request, Tecnico $tecnico)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'especialidad' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $tecnico->update($datos);

        return response()->json(['mensaje' => 'Técnico actualizado correctamente', 'data' => $tecnico]);
    }

    public function destroy(Tecnico $tecnico)
    {
        $tecnico->delete();

        return response()->json(['mensaje' => 'Técnico eliminado correctamente']);
    }
}
