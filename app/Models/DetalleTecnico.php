<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleTecnico extends Model
{
    protected $table = 'detalles_tecnicos';

    protected $fillable = [
        'equipo_id',
        'gas_refrigerante',
        'voltaje',
        'amperaje_nominal',
        'presion_succion_psi',
        'presion_descarga_psi',
        'observaciones_tecnicas'
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }
}