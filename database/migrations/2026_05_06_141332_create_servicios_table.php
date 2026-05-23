<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tecnico_id')->constrained()->cascadeOnDelete();

            $table->string('tipo_servicio');
            $table->text('descripcion')->nullable();

            $table->date('fecha');
            $table->time('hora')->nullable();

            $table->decimal('costo', 10, 2)->default(0);
            $table->string('estado')->default('pendiente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};