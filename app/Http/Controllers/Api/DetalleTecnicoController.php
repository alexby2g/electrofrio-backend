<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleTecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DetalleTecnicoController extends Controller
{
    public function index()
    {
        return response()->json([
            'mensaje' => 'Listado de detalles técnicos',
            'data' => DetalleTecnico::with(['cita.cliente', 'cita.equipo', 'cita.servicio', 'tecnico'])->latest()->get()->map(fn ($detalle) => $detalle->aplicarEvidenciasConDataUrl()),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $detalle = DetalleTecnico::updateOrCreate(['cita_id' => $datos['cita_id']], $datos)
            ->load(['cita.cliente', 'cita.equipo', 'cita.servicio', 'tecnico']);

        return response()->json(['mensaje' => 'Detalle técnico guardado correctamente', 'data' => $detalle->aplicarEvidenciasConDataUrl()], 201);
    }

    public function show(DetalleTecnico $detalleTecnico)
    {
        return response()->json(['mensaje' => 'Detalle técnico', 'data' => $detalleTecnico->load(['cita.cliente', 'cita.equipo', 'cita.servicio', 'tecnico'])->aplicarEvidenciasConDataUrl()]);
    }

    public function update(Request $request, DetalleTecnico $detalleTecnico)
    {
        $detalleTecnico->update($this->validar($request));

        return response()->json(['mensaje' => 'Detalle técnico actualizado correctamente', 'data' => $detalleTecnico->load(['cita.cliente', 'cita.equipo', 'cita.servicio', 'tecnico'])->aplicarEvidenciasConDataUrl()]);
    }

    public function destroy(DetalleTecnico $detalleTecnico)
    {
        foreach (($detalleTecnico->evidencias ?? []) as $evidencia) {
            if (!empty($evidencia['path'])) {
                Storage::disk('public')->delete($evidencia['path']);
            }
        }

        $detalleTecnico->delete();

        return response()->json(['mensaje' => 'Detalle técnico eliminado correctamente']);
    }

    public function subirEvidencia(Request $request, DetalleTecnico $detalleTecnico)
    {
        $datos = $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp,gif,bmp|max:8192',
            'tipo' => ['required', Rule::in(['antes', 'durante', 'despues', 'firma', 'otro'])],
            'descripcion' => 'nullable|string|max:255',
        ]);

        $archivo = $request->file('foto');
        $path = $archivo->store("evidencias/detalles/{$detalleTecnico->id}", 'public');
        $idEvidencia = (string) Str::uuid();
        $urlArchivo = $request->getSchemeAndHttpHost() . "/api/detalle-tecnicos/{$detalleTecnico->id}/evidencias/{$idEvidencia}/archivo";

        $evidencias = $detalleTecnico->evidencias ?? [];
        $evidencias[] = [
            'id' => $idEvidencia,
            'tipo' => $datos['tipo'],
            'descripcion' => $datos['descripcion'] ?? null,
            'path' => $path,
            'url' => $urlArchivo,
            'archivo_url' => $urlArchivo,
            'nombre_original' => $archivo->getClientOriginalName(),
            'mime' => $archivo->getMimeType(),
            'size' => $archivo->getSize(),
            'created_at' => now()->toDateTimeString(),
        ];

        $detalleTecnico->forceFill(['evidencias' => $evidencias])->save();

        return response()->json([
            'mensaje' => 'Foto agregada correctamente al expediente técnico',
            'data' => $detalleTecnico->fresh()->load(['cita.cliente', 'cita.equipo', 'cita.servicio', 'tecnico'])->aplicarEvidenciasConDataUrl(),
        ], 201);
    }

    public function verEvidencia(DetalleTecnico $detalleTecnico, string $evidencia)
    {
        $evidencias = collect($detalleTecnico->evidencias ?? []);
        $archivo = null;

        foreach ($evidencias as $index => $item) {
            $coincide = (isset($item['id']) && (string) $item['id'] === (string) $evidencia)
                || ((string) $index === (string) $evidencia);

            if ($coincide) {
                $archivo = $item;
                break;
            }
        }

        abort_unless($archivo && !empty($archivo['path']), 404, 'Foto no encontrada en el expediente.');

        $path = $archivo['path'];
        abort_unless(Storage::disk('public')->exists($path), 404, 'El archivo físico de la foto no existe.');

        $mime = $archivo['mime'] ?? Storage::disk('public')->mimeType($path) ?? 'image/jpeg';

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function eliminarEvidencia(DetalleTecnico $detalleTecnico, string $evidencia)
    {
        $evidencias = collect($detalleTecnico->evidencias ?? []);
        $eliminada = null;

        $filtradas = $evidencias->reject(function ($item, $index) use ($evidencia, &$eliminada) {
            $coincide = (isset($item['id']) && (string) $item['id'] === (string) $evidencia)
                || ((string) $index === (string) $evidencia);

            if ($coincide) {
                $eliminada = $item;
            }

            return $coincide;
        })->values()->all();

        if ($eliminada && !empty($eliminada['path'])) {
            Storage::disk('public')->delete($eliminada['path']);
        }

        $detalleTecnico->forceFill(['evidencias' => $filtradas])->save();

        return response()->json([
            'mensaje' => $eliminada ? 'Foto eliminada correctamente' : 'No se encontró la foto indicada',
            'data' => $detalleTecnico->fresh()->load(['cita.cliente', 'cita.equipo', 'cita.servicio', 'tecnico'])->aplicarEvidenciasConDataUrl(),
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'tecnico_id' => 'nullable|exists:tecnicos,id',
            'diagnostico' => 'nullable|string',
            'trabajo_realizado' => 'nullable|string',
            'estado_equipo' => 'nullable|string|max:80',
            'garantia' => 'nullable|string|max:160',
            'recomendaciones' => 'nullable|string',
            'fecha_entrega' => 'nullable|date',
            'repuestos' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.cantidad' => 'nullable|numeric|min:0',
            'items.*.unidad' => 'nullable|string|max:30',
            'items.*.descripcion' => 'nullable|string|max:255',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
            'observacion' => 'nullable|string',
        ]);
    }
}
