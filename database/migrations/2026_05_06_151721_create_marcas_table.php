<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();

            // Nombre de la marca (LG, Samsung, etc)
            $table->string('nombre');

            // Tipo de equipo (split, ventana, inverter, etc)
            $table->string('tipo')->default('split');

            // (OPCIONAL PRO) logo de la marca
            $table->string('logo')->nullable();

            // Evita duplicados (misma marca + tipo)
            $table->unique(['nombre', 'tipo']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marcas');
    }
};