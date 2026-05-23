<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $fillable = [
        'cliente_id',
        'equipo_id',
        'tecnico_id',
        'tipo_servicio',
        'descripcion',
        'fecha',
        'hora',
        'costo',
        'estado',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
}
