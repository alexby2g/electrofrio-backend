<?php

namespace Tests\Feature;

use App\Models\Conversacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppManualFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_is_saved_and_prepared_for_whatsapp_business_app(): void
    {
        $administrador = User::factory()->create([
            'rol' => User::ROL_ADMINISTRADOR,
            'activo' => true,
        ]);

        $conversacion = Conversacion::create([
            'creado_por' => $administrador->id,
            'asunto' => 'Confirmación de atención',
            'tipo' => 'whatsapp',
            'canal_externo_id' => '73907925',
        ]);

        $conversacion->participantes()->attach($administrador->id);
        Sanctum::actingAs($administrador);

        $mensaje = 'Hola Carolina, su cita está confirmada.';

        $this->postJson("/api/conversaciones/{$conversacion->id}/mensajes", [
            'canal' => 'whatsapp_manual',
            'contenido' => $mensaje,
        ])->assertCreated()
            ->assertJsonPath('mensaje', 'Mensaje preparado para WhatsApp Business.')
            ->assertJsonPath('data.canal', 'whatsapp_manual')
            ->assertJsonPath('data.estado', 'preparado')
            ->assertJsonPath(
                'whatsapp_url',
                'https://wa.me/59173907925?text='.rawurlencode($mensaje)
            );

        $this->assertDatabaseHas('mensajes', [
            'conversacion_id' => $conversacion->id,
            'remitente_id' => $administrador->id,
            'canal' => 'whatsapp_manual',
            'contenido' => $mensaje,
            'estado' => 'preparado',
        ]);

        $this->assertDatabaseHas('conversaciones', [
            'id' => $conversacion->id,
            'canal_externo_id' => '+59173907925',
        ]);
    }
}
