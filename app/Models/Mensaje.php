<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes';

    protected $fillable = [
        'conversacion_id',
        'remitente_id',
        'canal',
        'contenido',
        'archivo_ruta',
        'archivo_nombre',
        'archivo_tipo',
        'mensaje_externo_id',
        'estado',
        'metadata',
        'enviado_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'enviado_at' => 'datetime',
    ];

    protected $appends = [
        'archivo_url',
    ];

    public function conversacion()
    {
        return $this->belongsTo(Conversacion::class);
    }

    public function remitente()
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    public function getArchivoUrlAttribute(): ?string
    {
        return $this->archivo_ruta
            ? Storage::disk('public')->url($this->archivo_ruta)
            : null;
    }
}
