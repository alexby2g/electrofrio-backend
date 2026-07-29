<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->date('fecha');
            $table->time('hora')->nullable();
            $table->enum('estado', [
                'pendiente',
                'revision',
                'en_proceso',
                'esperando_repuesto',
                'terminado',
                'entregado',
                'concluida',
                'cancelada',
            ])->default('pendiente');
            $table->text('descripcion')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
