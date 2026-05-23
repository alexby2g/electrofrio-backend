<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_servicio', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->text('descripcion')->nullable();

            // Precio sugerido (puedes cambiar luego)
            $table->decimal('precio_base', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_servicio');
    }
};