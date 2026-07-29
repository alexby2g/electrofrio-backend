<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('telefono', 20)->index();
            $table->string('canal', 20)->default('sms');
            $table->string('codigo_hash')->nullable();
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->timestamp('vence_at');
            $table->timestamp('usado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
