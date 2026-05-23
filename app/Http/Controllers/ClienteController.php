<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;

class ClienteController extends Controller
{
    public function index()
    {
        return response()->json(Cliente::orderBy('id', 'desc')->get());
    }

    public function store(StoreClienteRequest $request)
    {
        $cliente = Cliente::create($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Cliente registrado correctamente',
            'data' => $cliente,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Cliente::with(['equipos', 'servicios'])->findOrFail($id));
    }

    public function update(UpdateClienteRequest $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Cliente actualizado correctamente',
            'data' => $cliente,
        ]);
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json([
            'res' => true,
            'message' => 'Cliente eliminado correctamente',
        ]);
    }
}
