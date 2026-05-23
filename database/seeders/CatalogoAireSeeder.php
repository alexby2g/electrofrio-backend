<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoAireSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('marcas')->delete();
        DB::table('tipos_equipo')->delete();
        DB::table('capacidades')->delete();

        DB::table('marcas')->insert([
            ['nombre' => 'Samsung', 'tipo' => 'split', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'LG', 'tipo' => 'split', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Whirlpool', 'tipo' => 'split', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Midea', 'tipo' => 'split', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('tipos_equipo')->insert([
            ['nombre' => 'Split', 'descripcion' => 'Aire acondicionado tipo split', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ventana', 'descripcion' => 'Aire acondicionado de ventana', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Piso Techo', 'descripcion' => 'Aire acondicionado piso techo', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Central', 'descripcion' => 'Sistema central de aire acondicionado', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('capacidades')->insert([
            ['btu' => '9000 BTU', 'descripcion' => 'Capacidad pequeña', 'created_at' => now(), 'updated_at' => now()],
            ['btu' => '12000 BTU', 'descripcion' => 'Capacidad estándar', 'created_at' => now(), 'updated_at' => now()],
            ['btu' => '18000 BTU', 'descripcion' => 'Capacidad media', 'created_at' => now(), 'updated_at' => now()],
            ['btu' => '24000 BTU', 'descripcion' => 'Capacidad alta', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
