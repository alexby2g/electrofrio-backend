<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_connections', function (Blueprint $table) {
            $table->id();
            $table->string('config_id')->nullable()->index();
            $table->string('business_id')->nullable();
            $table->string('waba_id')->index();
            $table->string('phone_number_id')->unique();
            $table->string('display_phone_number')->nullable();
            $table->string('verified_name')->nullable();
            $table->string('quality_rating')->nullable();
            $table->longText('access_token');
            $table->string('token_type')->nullable();
            $table->timestampTz('token_expires_at')->nullable();
            $table->string('status')->default('connected')->index();
            $table->json('metadata')->nullable();
            $table->timestampTz('connected_at')->nullable();
            $table->timestampTz('last_verified_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_connections');
    }
};
