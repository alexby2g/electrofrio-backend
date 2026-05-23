<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    protected $fillable = ['nombre', 'telefono', 'especialidad'];

    public function servicios()
    {
        return $this->hasMany(Servicio::class);
    }
}
