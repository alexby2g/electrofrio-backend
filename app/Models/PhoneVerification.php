<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    protected $fillable = [
        'user_id',
        'telefono',
        'canal',
        'codigo_hash',
        'intentos',
        'vence_at',
        'usado_at',
    ];

    protected $casts = [
        'vence_at' => 'datetime',
        'usado_at' => 'datetime',
    ];
}
