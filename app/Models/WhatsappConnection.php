<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConnection extends Model
{
    protected $fillable = [
        'config_id',
        'business_id',
        'waba_id',
        'phone_number_id',
        'display_phone_number',
        'verified_name',
        'quality_rating',
        'access_token',
        'token_type',
        'token_expires_at',
        'status',
        'metadata',
        'connected_at',
        'last_verified_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'metadata' => 'array',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];
}
