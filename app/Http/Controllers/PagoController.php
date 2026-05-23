<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\UpdatePagoRequest;

class PagoController extends Controller
{
    public function index()
    {
        return response()->json(Pago::with(['servicio.cliente', 'servicio.equipo'])->orderBy('id', 'desc')->get());
    }

    public function store(StorePagoRequest $request)
    {
        $pago = Pago::create($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Pago registrado correctamente',
            'data' => $pago,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Pago::with(['servicio.cliente', 'servicio.equipo'])->findOrFail($id));
    }

    public function update(UpdatePagoRequest $request, $id)
    {
        $pago = Pago::findOrFail($id);
        $pago->update($request->validated());

        return response()->json([
            'res' => true,
            'message' => 'Pago actualizado correctamente',
            'data' => $pago,
        ]);
    }

    public function destroy($id)
    {
        $pago = Pago::findOrFail($id);
        $pago->delete();

        return response()->json([
            'res' => true,
            'message' => 'Registro de pago eliminado correctamente',
        ]);
    }
}
