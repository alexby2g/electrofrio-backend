<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DetalleTecnico extends Model
{
    use HasFactory;

    protected $table = 'detalle_tecnicos';

    protected $fillable = [
        'cita_id',
        'tecnico_id',
        'diagnostico',
        'trabajo_realizado',
        'estado_equipo',
        'garantia',
        'garantia_dias',
        'garantia_inicio',
        'garantia_fin',
        'condiciones_garantia',
        'recomendaciones',
        'fecha_entrega',
        'repuestos',
        'items',
        'evidencias',
        'observacion',
    ];

    protected $casts = [
        'fecha_entrega' => 'date:Y-m-d',
        'garantia_dias' => 'integer',
        'garantia_inicio' => 'date:Y-m-d',
        'garantia_fin' => 'date:Y-m-d',
        'items' => 'array',
        'evidencias' => 'array',
    ];


    /**
     * Devuelve las evidencias listas para mostrarse en el navegador/PDF.
     * Además de la ruta normal, agrega data_url en base64 para evitar fallas
     * con /storage, CORS o enlaces simbólicos en Laragon/Windows.
     */
    public function evidenciasConDataUrl(): array
    {
        $evidencias = $this->evidencias ?? [];

        if (!is_array($evidencias)) {
            return [];
        }

        return collect($evidencias)->map(function ($evidencia, $index) {
            if (!is_array($evidencia)) {
                return $evidencia;
            }

            $path = $evidencia['path'] ?? null;
            $evidenciaId = $evidencia['id'] ?? $index;
            $evidencia['archivo_url'] = URL::temporarySignedRoute(
                'evidencias.archivo',
                now()->addHours(4),
                [
                    'detalleTecnico' => $this->id,
                    'evidencia' => $evidenciaId,
                ]
            );
            $evidencia['url'] = $evidencia['archivo_url'];

            if (!empty($evidencia['data_url'])) {
                return $evidencia;
            }

            if ($path && Storage::disk('public')->exists($path)) {
                try {
                    $mime = $evidencia['mime'] ?? Storage::disk('public')->mimeType($path) ?? 'image/jpeg';
                    $contenido = Storage::disk('public')->get($path);
                    $evidencia['data_url'] = 'data:' . $mime . ';base64,' . base64_encode($contenido);
                } catch (\Throwable $error) {
                    // Si algo falla, mantenemos la URL normal como respaldo.
                }
            }

            return $evidencia;
        })->values()->all();
    }

    public function aplicarEvidenciasConDataUrl(): self
    {
        $this->setAttribute('evidencias', $this->evidenciasConDataUrl());

        return $this;
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }
}
