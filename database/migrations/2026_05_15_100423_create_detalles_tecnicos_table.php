<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalles_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('gas_refrigerante')->nullable();
            $table->string('voltaje')->nullable();
            $table->decimal('amperaje_nominal', 8, 2)->nullable();
            $table->integer('presion_succion_psi')->nullable();
            $table->integer('presion_descarga_psi')->nullable();
            $table->text('observaciones_tecnicas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_tecnicos');
    }
};
