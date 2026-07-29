<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'cita_id',
        'cliente_id',
        'monto',
        'metodo_pago',
        'estado',
        'fecha_pago',
        'observacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date:Y-m-d',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
