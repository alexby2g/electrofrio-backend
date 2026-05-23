<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capacidad extends Model
{
    protected $table = 'capacidades';
    protected $fillable = ['btu', 'descripcion'];
}
