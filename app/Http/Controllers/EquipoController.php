<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;

class EquipoController extends Controller
{
    public function index()
    {
        return response()->json(Equipo::with(['cliente', 'detalleTecnico'])->orderBy('id', 'desc')->get());
    }

    public function store(StoreEquipoRequest $request)
    {
        $equipo = Equipo::create($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Equipo registrado correctamente',
            'data' => $equipo,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Equipo::with(['cliente', 'detalleTecnico', 'servicios'])->findOrFail($id));
    }

    public function update(UpdateEquipoRequest $request, $id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->update($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Equipo actualizado correctamente',
            'data' => $equipo,
        ]);
    }

    public function destroy($id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->delete();

        return response()->json([
            'res' => true,
            'message' => 'Equipo eliminado correctamente',
        ]);
    }
}
