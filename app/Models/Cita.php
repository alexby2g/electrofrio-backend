<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    public const ETAPAS = [
        'cita',
        'diagnostico',
        'propuesta',
        'servicio',
        'pago',
        'garantia',
        'cerrada',
    ];

    public const DECISIONES = ['pendiente', 'aceptado', 'rechazado'];

    protected $fillable = [
        'cliente_id',
        'tecnico_id',
        'servicio_id',
        'equipo_id',
        'canal_contacto',
        'prioridad',
        'direccion_servicio',
        'referencia_ubicacion',
        'problema_reportado',
        'fecha',
        'hora',
        'estado',
        'etapa',
        'descripcion',
        'propuesta',
        'costo_mano_obra',
        'costo_materiales',
        'descuento',
        'decision_cliente',
        'motivo_rechazo',
        'decision_at',
        'cerrado_at',
        'total',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'decision_at' => 'datetime',
        'cerrado_at' => 'datetime',
        'costo_mano_obra' => 'decimal:2',
        'costo_materiales' => 'decimal:2',
        'descuento' => 'decimal:2',
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
