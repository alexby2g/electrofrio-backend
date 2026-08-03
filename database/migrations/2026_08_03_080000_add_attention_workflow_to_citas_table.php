<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->string('canal_contacto', 30)->default('llamada')->after('equipo_id');
            $table->string('prioridad', 20)->default('normal')->after('canal_contacto');
            $table->string('direccion_servicio')->nullable()->after('prioridad');
            $table->string('referencia_ubicacion')->nullable()->after('direccion_servicio');
            $table->text('problema_reportado')->nullable()->after('referencia_ubicacion');
            $table->string('etapa', 30)->default('cita')->after('estado')->index();
            $table->text('propuesta')->nullable()->after('descripcion');
            $table->decimal('costo_mano_obra', 10, 2)->default(0)->after('propuesta');
            $table->decimal('costo_materiales', 10, 2)->default(0)->after('costo_mano_obra');
            $table->decimal('descuento', 10, 2)->default(0)->after('costo_materiales');
            $table->string('decision_cliente', 20)->default('pendiente')->after('descuento')->index();
            $table->text('motivo_rechazo')->nullable()->after('decision_cliente');
            $table->timestamp('decision_at')->nullable()->after('motivo_rechazo');
            $table->timestamp('cerrado_at')->nullable()->after('decision_at');
        });

        DB::table('citas')
            ->where('estado', 'revision')
            ->update(['etapa' => 'diagnostico']);

        DB::table('citas')
            ->whereIn('estado', ['en_proceso', 'esperando_repuesto', 'terminado', 'entregado', 'concluida'])
            ->update([
                'etapa' => 'servicio',
                'decision_cliente' => 'aceptado',
                'decision_at' => DB::raw('updated_at'),
            ]);

        DB::table('citas')
            ->where('estado', 'cancelada')
            ->update([
                'etapa' => 'cerrada',
                'decision_cliente' => 'rechazado',
                'decision_at' => DB::raw('updated_at'),
                'cerrado_at' => DB::raw('updated_at'),
            ]);

        DB::table('citas')
            ->where('costo_mano_obra', 0)
            ->where('total', '>', 0)
            ->update(['costo_mano_obra' => DB::raw('total')]);
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropIndex(['etapa']);
            $table->dropIndex(['decision_cliente']);
            $table->dropColumn([
                'canal_contacto',
                'prioridad',
                'direccion_servicio',
                'referencia_ubicacion',
                'problema_reportado',
                'etapa',
                'propuesta',
                'costo_mano_obra',
                'costo_materiales',
                'descuento',
                'decision_cliente',
                'motivo_rechazo',
                'decision_at',
                'cerrado_at',
            ]);
        });
    }
};
