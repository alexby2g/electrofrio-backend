<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Pago;
use App\Models\Servicio;
use App\Models\Tecnico;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        $cliente1 = Cliente::create([
            'nombre' => 'Salteñas Hamacas',
            'telefono' => '70000001',
            'direccion' => 'Av. 6 de Agosto',
            'equipo' => 'Aire Split',
            'marca' => 'General Lux',
            'observacion' => 'Cliente frecuente de mantenimiento.',
        ]);

        $cliente2 = Cliente::create([
            'nombre' => 'Cliente Particular',
            'telefono' => '70000002',
            'direccion' => 'Zona Central',
            'equipo' => 'Refrigerador',
            'marca' => 'Mabe',
        ]);

        $tecnico1 = Tecnico::create([
            'nombre' => 'Carlos Enrique Guzmán',
            'telefono' => '70000003',
            'especialidad' => 'Aires acondicionados y refrigeración',
        ]);

        $tecnico2 = Tecnico::create([
            'nombre' => 'Técnico auxiliar',
            'telefono' => '70000004',
            'especialidad' => 'Instalación y limpieza',
        ]);

        $servicio1 = Servicio::create([
            'nombre' => 'Mantenimiento de aire acondicionado',
            'descripcion' => 'Limpieza interna y externa, revisión eléctrica, drenaje y refrigerante.',
            'precio' => 150,
        ]);

        $servicio2 = Servicio::create([
            'nombre' => 'Revisión técnica de refrigerador',
            'descripcion' => 'Diagnóstico general del equipo y recomendaciones.',
            'precio' => 80,
        ]);

        Equipo::create([
            'cliente_id' => $cliente1->id,
            'tipo' => 'Aire Split',
            'marca' => 'General Lux',
            'modelo' => '12000 BTU',
            'ubicacion' => 'Área de atención',
        ]);

        Equipo::create([
            'cliente_id' => $cliente2->id,
            'tipo' => 'Refrigerador',
            'marca' => 'Mabe',
            'ubicacion' => 'Domicilio',
        ]);

        $cita = Cita::create([
            'cliente_id' => $cliente1->id,
            'tecnico_id' => $tecnico1->id,
            'servicio_id' => $servicio1->id,
            'fecha' => now()->toDateString(),
            'hora' => '09:00',
            'estado' => 'pendiente',
            'descripcion' => 'Mantenimiento preventivo de equipos split.',
            'total' => 150,
        ]);

        Cita::create([
            'cliente_id' => $cliente2->id,
            'tecnico_id' => $tecnico2->id,
            'servicio_id' => $servicio2->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora' => '15:30',
            'estado' => 'en_proceso',
            'descripcion' => 'Revisión por baja refrigeración.',
            'total' => 80,
        ]);

        Pago::create([
            'cita_id' => $cita->id,
            'cliente_id' => $cliente1->id,
            'monto' => 150,
            'metodo_pago' => 'efectivo',
            'estado' => 'pagado',
            'fecha_pago' => now()->toDateString(),
            'observacion' => 'Pago de prueba cargado desde seeder.',
        ]);
    }
}
