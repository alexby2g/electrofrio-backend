<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_tecnicos', function (Blueprint $table) {
            if (!Schema::hasColumn('detalle_tecnicos', 'estado_equipo')) {
                $table->string('estado_equipo', 80)->nullable()->after('trabajo_realizado');
            }

            if (!Schema::hasColumn('detalle_tecnicos', 'garantia')) {
                $table->string('garantia', 160)->nullable()->after('estado_equipo');
            }

            if (!Schema::hasColumn('detalle_tecnicos', 'recomendaciones')) {
                $table->text('recomendaciones')->nullable()->after('garantia');
            }

            if (!Schema::hasColumn('detalle_tecnicos', 'fecha_entrega')) {
                $table->date('fecha_entrega')->nullable()->after('recomendaciones');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_tecnicos', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_tecnicos', 'fecha_entrega')) {
                $table->dropColumn('fecha_entrega');
            }

            if (Schema::hasColumn('detalle_tecnicos', 'recomendaciones')) {
                $table->dropColumn('recomendaciones');
            }

            if (Schema::hasColumn('detalle_tecnicos', 'garantia')) {
                $table->dropColumn('garantia');
            }

            if (Schema::hasColumn('detalle_tecnicos', 'estado_equipo')) {
                $table->dropColumn('estado_equipo');
            }
        });
    }
};
