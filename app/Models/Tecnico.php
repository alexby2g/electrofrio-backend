<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'telefono',
        'especialidad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

    public function detallesTecnicos()
    {
        return $this->hasMany(DetalleTecnico::class);
    }

    public function usuario()
    {
        return $this->hasOne(User::class);
    }
}
