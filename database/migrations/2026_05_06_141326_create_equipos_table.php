<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();

            $table->string('tipo');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('capacidad')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};