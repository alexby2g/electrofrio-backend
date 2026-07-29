<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'tecnico_id',
        'servicio_id',
        'equipo_id',
        'fecha',
        'hora',
        'estado',
        'descripcion',
        'total',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'total' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function detalleTecnico()
    {
        return $this->hasOne(DetalleTecnico::class);
    }

    public function conversaciones()
    {
        return $this->hasMany(Conversacion::class);
    }
}
