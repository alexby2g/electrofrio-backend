<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_tecnicos', function (Blueprint $table) {
            $table->unsignedInteger('garantia_dias')->default(0)->after('garantia');
            $table->date('garantia_inicio')->nullable()->after('garantia_dias');
            $table->date('garantia_fin')->nullable()->after('garantia_inicio')->index();
            $table->text('condiciones_garantia')->nullable()->after('garantia_fin');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_tecnicos', function (Blueprint $table) {
            $table->dropIndex(['garantia_fin']);
            $table->dropColumn([
                'garantia_dias',
                'garantia_inicio',
                'garantia_fin',
                'condiciones_garantia',
            ]);
        });
    }
};
