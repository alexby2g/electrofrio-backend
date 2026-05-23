<?php

namespace App\Http\Controllers;

use App\Models\DetalleTecnico;
use App\Http\Requests\StoreDetalleTecnicoRequest; 
use App\Http\Requests\UpdateDetalleTecnicoRequest; 
use Illuminate\Http\Request;

class DetalleTecnicoController extends Controller
{
    
    public function index()
    {
        return response()->json(
            DetalleTecnico::with('equipo')->get()
        );
    }

   
    public function store(StoreDetalleTecnicoRequest $request)
    {
      
        $detalle = DetalleTecnico::create($request->validated());

        return response()->json([
            'mensaje' => 'Detalle técnico creado correctamente',
            'data' => $detalle
        ], 201);
    }

   
    public function show($id)
    {
        $detalle = DetalleTecnico::with('equipo')->findOrFail($id);

        return response()->json($detalle);
    }

  
    public function update(UpdateDetalleTecnicoRequest $request, $id)
    {
        $detalle = DetalleTecnico::findOrFail($id);

    
        $detalle->update($request->validated());

        return response()->json([
            'mensaje' => 'Detalle técnico actualizado correctamente',
            'data' => $detalle
        ]);
    }

   
    public function destroy($id)
    {
        $detalle = DetalleTecnico::findOrFail($id);
        $detalle->delete();

        return response()->json([
            'mensaje' => 'Detalle técnico eliminado correctamente'
        ]);
    }
}