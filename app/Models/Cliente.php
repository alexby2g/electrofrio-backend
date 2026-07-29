<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'telefono',
        'direccion',
        'equipo',
        'marca',
        'observacion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }
}
