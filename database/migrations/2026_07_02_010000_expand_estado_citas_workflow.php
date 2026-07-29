<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('citas') && Schema::hasColumn('citas', 'estado')) {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE citas MODIFY estado ENUM('pendiente','revision','en_proceso','esperando_repuesto','terminado','entregado','concluida','cancelada') NOT NULL DEFAULT 'pendiente'");
            }

            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_estado_check');
                DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_estado_check CHECK (estado IN ('pendiente','revision','en_proceso','esperando_repuesto','terminado','entregado','concluida','cancelada'))");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('citas') && Schema::hasColumn('citas', 'estado')) {
            DB::statement("UPDATE citas SET estado = 'concluida' WHERE estado IN ('terminado','entregado')");
            DB::statement("UPDATE citas SET estado = 'en_proceso' WHERE estado IN ('revision','esperando_repuesto')");

            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE citas MODIFY estado ENUM('pendiente','en_proceso','concluida','cancelada') NOT NULL DEFAULT 'pendiente'");
            }

            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE citas DROP CONSTRAINT IF EXISTS citas_estado_check');
                DB::statement("ALTER TABLE citas ADD CONSTRAINT citas_estado_check CHECK (estado IN ('pendiente','en_proceso','concluida','cancelada'))");
            }
        }
    }
};
