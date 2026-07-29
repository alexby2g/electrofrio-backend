<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('asunto', 180);
            $table->string('tipo', 30)->default('interna');
            $table->string('canal_externo_id')->nullable()->index();
            $table->timestamp('ultimo_mensaje_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversacion_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('leido_hasta_at')->nullable();
            $table->boolean('notificaciones')->default(true);
            $table->timestamps();
            $table->unique(['conversacion_id', 'user_id']);
        });

        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('remitente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('canal', 30)->default('interno');
            $table->text('contenido')->nullable();
            $table->string('archivo_ruta')->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->string('archivo_tipo', 120)->nullable();
            $table->string('mensaje_externo_id')->nullable()->unique();
            $table->string('estado', 30)->default('enviado');
            $table->json('metadata')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();
            $table->index(['conversacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
        Schema::dropIfExists('conversacion_usuario');
        Schema::dropIfExists('conversaciones');
    }
};
