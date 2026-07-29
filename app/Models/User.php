<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROL_ADMINISTRADOR = 'administrador';
    public const ROL_RECEPCION = 'recepcion';
    public const ROL_TECNICO = 'tecnico';

    public const ROLES = [
        self::ROL_ADMINISTRADOR,
        self::ROL_RECEPCION,
        self::ROL_TECNICO,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'telefono',
        'telefono_verificado_at',
        'password',
        'rol',
        'activo',
        'tecnico_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'telefono_verificado_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function conversaciones()
    {
        return $this->belongsToMany(Conversacion::class, 'conversacion_usuario')
            ->withPivot(['leido_hasta_at', 'notificaciones'])
            ->withTimestamps();
    }

    public function mensajes()
    {
        return $this->hasMany(Mensaje::class, 'remitente_id');
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }
}
