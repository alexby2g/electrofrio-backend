<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $fillable = ['cliente_id', 'tipo', 'marca', 'modelo', 'capacidad'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class);
    }

    public function detalleTecnico()
    {
        return $this->hasOne(DetalleTecnico::class);
    }
}
