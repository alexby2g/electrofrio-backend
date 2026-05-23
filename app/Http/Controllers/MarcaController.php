<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Http\Requests\StoreMarcaRequest;

class MarcaController extends Controller
{
    public function index()
    {
        return response()->json(Marca::orderBy('nombre', 'asc')->get());
    }

    public function store(StoreMarcaRequest $request)
    {
        $marca = Marca::create($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Marca registrada correctamente',
            'data' => $marca,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Marca::findOrFail($id));
    }

    public function update(StoreMarcaRequest $request, $id)
    {
        $marca = Marca::findOrFail($id);
        $marca->update($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Marca actualizada correctamente',
            'data' => $marca,
        ]);
    }

    public function destroy($id)
    {
        $marca = Marca::findOrFail($id);
        $marca->delete();

        return response()->json([
            'res' => true,
            'message' => 'Marca eliminada correctamente',
        ]);
    }
}
