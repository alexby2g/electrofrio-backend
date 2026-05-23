<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            
            $table->string('metodo_pago')->default('Efectivo'); 
            
            $table->string('estado')->default('Completado'); 
            
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};