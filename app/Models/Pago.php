<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'servicio_id',
        'monto',
        'fecha_pago',
        'metodo_pago',
        'estado',
        'observaciones'
    ];

    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }
}