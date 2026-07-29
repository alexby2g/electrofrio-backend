<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telefono')) {
                $table->string('telefono', 20)->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'telefono_verificado_at')) {
                $table->timestamp('telefono_verificado_at')->nullable()->after('telefono');
            }

            if (!Schema::hasColumn('users', 'tecnico_id')) {
                $table->foreignId('tecnico_id')
                    ->nullable()
                    ->unique()
                    ->after('activo')
                    ->constrained('tecnicos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tecnico_id')) {
                $table->dropConstrainedForeignId('tecnico_id');
            }

            if (Schema::hasColumn('users', 'telefono_verificado_at')) {
                $table->dropColumn('telefono_verificado_at');
            }

            if (Schema::hasColumn('users', 'telefono')) {
                $table->dropUnique('users_telefono_unique');
                $table->dropColumn('telefono');
            }
        });
    }
};
