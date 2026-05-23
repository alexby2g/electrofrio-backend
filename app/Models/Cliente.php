<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = ['nombre', 'telefono', 'direccion'];

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class);
    }
}
