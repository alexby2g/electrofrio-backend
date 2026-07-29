<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('servicios', 'categoria')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->string('categoria', 80)->nullable()->after('descripcion')->index();
            });
        }

        DB::table('servicios')
            ->whereNull('categoria')
            ->where(function ($query) {
                $query->where('nombre', 'like', '%diagn%')
                    ->orWhere('nombre', 'like', '%revisi%')
                    ->orWhere('descripcion', 'like', '%diagn%')
                    ->orWhere('descripcion', 'like', '%revisi%');
            })
            ->update(['categoria' => 'diagnostico']);

        DB::table('servicios')
            ->whereNull('categoria')
            ->where(function ($query) {
                $query->where('nombre', 'like', '%prevent%')
                    ->orWhere('nombre', 'like', '%mantenimiento%')
                    ->orWhere('nombre', 'like', '%limpieza%')
                    ->orWhere('descripcion', 'like', '%prevent%')
                    ->orWhere('descripcion', 'like', '%mantenimiento%')
                    ->orWhere('descripcion', 'like', '%limpieza%');
            })
            ->update(['categoria' => 'preventivo']);

        DB::table('servicios')
            ->whereNull('categoria')
            ->where(function ($query) {
                $query->where('nombre', 'like', '%instal%')
                    ->orWhere('nombre', 'like', '%reubic%')
                    ->orWhere('descripcion', 'like', '%instal%')
                    ->orWhere('descripcion', 'like', '%reubic%');
            })
            ->update(['categoria' => 'instalacion']);

        DB::table('servicios')
            ->whereNull('categoria')
            ->where(function ($query) {
                $query->where('nombre', 'like', '%correct%')
                    ->orWhere('nombre', 'like', '%repar%')
                    ->orWhere('nombre', 'like', '%fuga%')
                    ->orWhere('nombre', 'like', '%compresor%')
                    ->orWhere('nombre', 'like', '%capacitor%')
                    ->orWhere('descripcion', 'like', '%correct%')
                    ->orWhere('descripcion', 'like', '%repar%')
                    ->orWhere('descripcion', 'like', '%fuga%')
                    ->orWhere('descripcion', 'like', '%compresor%')
                    ->orWhere('descripcion', 'like', '%capacitor%');
            })
            ->update(['categoria' => 'correctivo']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('servicios', 'categoria')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->dropColumn('categoria');
            });
        }
    }
};
