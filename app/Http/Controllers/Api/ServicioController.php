<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->query('buscar');
        $categoria = $request->query('categoria');

        $servicios = Servicio::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($subQuery) use ($buscar) {
                    $subQuery->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%")
                        ->orWhere('categoria', 'like', "%{$buscar}%");
                });
            })
            ->when($categoria && $categoria !== 'todos', function ($query) use ($categoria) {
                $query->where('categoria', $categoria);
            })
            ->latest()
            ->get();

        return response()->json([
            'mensaje' => 'Listado de servicios',
            'data' => $servicios,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:80',
            'precio' => 'required|numeric|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $servicio = Servicio::create($datos);

        return response()->json([
            'mensaje' => 'Servicio registrado correctamente',
            'data' => $servicio,
        ], 201);
    }

    public function show(Servicio $servicio)
    {
        return response()->json([
            'mensaje' => 'Detalle del servicio',
            'data' => $servicio,
        ]);
    }

    public function update(Request $request, Servicio $servicio)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:80',
            'precio' => 'required|numeric|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $servicio->update($datos);

        return response()->json([
            'mensaje' => 'Servicio actualizado correctamente',
            'data' => $servicio,
        ]);
    }

    public function destroy(Servicio $servicio)
    {
        $servicio->delete();

        return response()->json([
            'mensaje' => 'Servicio eliminado correctamente',
        ]);
    }

    public function resumen()
    {
        return response()->json([
            'mensaje' => 'Resumen de servicios',
            'total_servicios' => Servicio::count(),
            'servicios_activos' => Servicio::where('activo', true)->count(),
            'precio_promedio' => Servicio::avg('precio') ?? 0,
            'servicios' => Servicio::latest()->take(5)->get(),
        ]);
    }
}
