<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Http\Requests\StoreServicioRequest;
use App\Http\Requests\UpdateServicioRequest;

class ServicioController extends Controller
{
    public function index()
    {
        return response()->json(Servicio::with(['cliente', 'tecnico', 'equipo', 'pagos'])->orderBy('id', 'desc')->get());
    }

    public function store(StoreServicioRequest $request)
    {
        $servicio = Servicio::create($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Servicio registrado correctamente',
            'data' => $servicio,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Servicio::with(['cliente', 'tecnico', 'equipo', 'pagos'])->findOrFail($id));
    }

    public function update(UpdateServicioRequest $request, $id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->update($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Servicio actualizado correctamente',
            'data' => $servicio,
        ]);
    }

    public function destroy($id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->delete();

        return response()->json([
            'res' => true,
            'message' => 'Servicio eliminado correctamente',
        ]);
    }
}
