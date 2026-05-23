<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use App\Http\Requests\StoreTecnicoRequest;
use App\Http\Requests\UpdateTecnicoRequest;

class TecnicoController extends Controller
{
    public function index()
    {
        return response()->json(Tecnico::orderBy('id', 'desc')->get());
    }

    public function store(StoreTecnicoRequest $request)
    {
        $tecnico = Tecnico::create($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Técnico registrado correctamente',
            'data' => $tecnico,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Tecnico::with('servicios')->findOrFail($id));
    }

    public function update(UpdateTecnicoRequest $request, $id)
    {
        $tecnico = Tecnico::findOrFail($id);
        $tecnico->update($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Técnico actualizado correctamente',
            'data' => $tecnico,
        ]);
    }

    public function destroy($id)
    {
        $tecnico = Tecnico::findOrFail($id);
        $tecnico->delete();

        return response()->json([
            'res' => true,
            'message' => 'Técnico eliminado correctamente',
        ]);
    }
}
