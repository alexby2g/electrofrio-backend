<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversacion extends Model
{
    use HasFactory;

    protected $table = 'conversaciones';

    protected $fillable = [
        'cita_id',
        'creado_por',
        'asunto',
        'tipo',
        'canal_externo_id',
        'ultimo_mensaje_at',
    ];

    protected $casts = [
        'ultimo_mensaje_at' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function participantes()
    {
        return $this->belongsToMany(User::class, 'conversacion_usuario')
            ->withPivot(['leido_hasta_at', 'notificaciones'])
            ->withTimestamps();
    }

    public function mensajes()
    {
        return $this->hasMany(Mensaje::class);
    }

    public function ultimoMensaje()
    {
        return $this->hasOne(Mensaje::class)->latestOfMany();
    }
}
