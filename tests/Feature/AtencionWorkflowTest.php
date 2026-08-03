<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\DetalleTecnico;
use App\Models\Pago;
use App\Services\AtencionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AtencionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_attention_advances_from_diagnosis_to_warranty(): void
    {
        $workflow = app(AtencionWorkflowService::class);
        $cita = $this->crearCita();
        $detalle = DetalleTecnico::create([
            'cita_id' => $cita->id,
            'diagnostico' => 'Capacitor dañado y filtros sucios.',
            'items' => [
                [
                    'tipo' => 'mano_obra',
                    'cantidad' => 1,
                    'unidad' => 'serv.',
                    'descripcion' => 'Reparación y mantenimiento',
                    'precio_unitario' => 250,
                    'subtotal' => 250,
                ],
                [
                    'tipo' => 'material',
                    'cantidad' => 1,
                    'unidad' => 'un.',
                    'descripcion' => 'Capacitor',
                    'precio_unitario' => 80,
                    'subtotal' => 80,
                ],
            ],
        ]);

        $cita->update(['propuesta' => 'Cambiar capacitor y realizar mantenimiento.']);
        $workflow->sincronizarDesdeDetalle($detalle->fresh('cita'));
        $this->assertSame('propuesta', $cita->fresh()->etapa);

        $workflow->decidir($cita->fresh(), 'aceptado');
        $this->assertSame('servicio', $cita->fresh()->etapa);

        $detalle->update([
            'trabajo_realizado' => 'Se cambió el capacitor y se limpiaron los filtros.',
            'garantia_dias' => 30,
            'garantia_inicio' => now()->toDateString(),
            'garantia_fin' => now()->addDays(30)->toDateString(),
        ]);
        $workflow->sincronizarDesdeDetalle($detalle->fresh('cita'));
        $workflow->finalizarServicio($cita->fresh());
        $this->assertSame('pago', $cita->fresh()->etapa);

        $pago = Pago::create([
            'cita_id' => $cita->id,
            'cliente_id' => $cita->cliente_id,
            'monto' => 330,
            'metodo_pago' => 'efectivo',
            'estado' => 'pagado',
            'fecha_pago' => now()->toDateString(),
        ]);
        $workflow->sincronizarDesdePago($pago->fresh('cita'));

        $this->assertSame('garantia', $cita->fresh()->etapa);
        $this->assertSame('entregado', $cita->fresh()->estado);
    }

    public function test_rejected_proposal_closes_attention_and_requires_a_reason(): void
    {
        $workflow = app(AtencionWorkflowService::class);
        $cita = $this->crearCita();
        DetalleTecnico::create([
            'cita_id' => $cita->id,
            'diagnostico' => 'Compresor sin presión.',
        ]);
        $cita->update(['propuesta' => 'Reemplazar el compresor.']);

        try {
            $workflow->decidir($cita->fresh(), 'rechazado');
            $this->fail('La decisión rechazada debía exigir un motivo.');
        } catch (ValidationException) {
            $this->assertSame('pendiente', $cita->fresh()->decision_cliente);
        }

        $workflow->decidir($cita->fresh(), 'rechazado', 'El cliente no aceptó el precio.');

        $cita->refresh();
        $this->assertSame('rechazado', $cita->decision_cliente);
        $this->assertSame('cerrada', $cita->etapa);
        $this->assertSame('cancelada', $cita->estado);
        $this->assertNotNull($cita->cerrado_at);
    }

    public function test_work_cannot_be_registered_before_customer_acceptance(): void
    {
        $workflow = app(AtencionWorkflowService::class);
        $cita = $this->crearCita();
        $detalle = DetalleTecnico::create([
            'cita_id' => $cita->id,
            'diagnostico' => 'Fuga detectada.',
            'trabajo_realizado' => 'Se selló la fuga.',
        ]);

        $this->expectException(ValidationException::class);
        $workflow->sincronizarDesdeDetalle($detalle->fresh('cita'));
    }

    private function crearCita(): Cita
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente de prueba',
            'telefono' => '70000000',
            'direccion' => 'Trinidad, Beni',
            'activo' => true,
        ]);

        return Cita::create([
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'hora' => '09:00',
            'estado' => 'pendiente',
            'etapa' => 'cita',
            'decision_cliente' => 'pendiente',
            'costo_mano_obra' => 250,
            'costo_materiales' => 80,
            'descuento' => 0,
            'total' => 330,
        ]);
    }
}
