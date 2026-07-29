<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_tecnicos', function (Blueprint $table) {
            if (!Schema::hasColumn('detalle_tecnicos', 'items')) {
                $table->json('items')->nullable()->after('repuestos');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_tecnicos', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_tecnicos', 'items')) {
                $table->dropColumn('items');
            }
        });
    }
};
