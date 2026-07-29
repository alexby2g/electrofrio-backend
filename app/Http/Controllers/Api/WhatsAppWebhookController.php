<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function verificar(Request $request)
    {
        $token = config('services.whatsapp.verify_token');

        if (
            $request->query('hub_mode') === 'subscribe'
            && $token
            && hash_equals($token, (string) $request->query('hub_verify_token'))
        ) {
            return response((string) $request->query('hub_challenge'), 200);
        }

        return response('Token de verificación inválido.', 403);
    }

    public function recibir(Request $request)
    {
        $this->validarFirma($request);

        foreach ((array) data_get($request->all(), 'entry', []) as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                foreach ((array) data_get($change, 'value.messages', []) as $externo) {
                    $this->guardarMensaje($externo);
                }

                foreach ((array) data_get($change, 'value.statuses', []) as $estado) {
                    $this->actualizarEstado($estado);
                }
            }
        }

        return response()->json(['recibido' => true]);
    }

    private function guardarMensaje(array $externo): void
    {
        $externoId = $externo['id'] ?? null;

        if (!$externoId || Mensaje::where('mensaje_externo_id', $externoId)->exists()) {
            return;
        }

        $telefono = PhoneNumber::normalize($externo['from'] ?? null);
        $cliente = Cliente::query()->get()->first(
            fn (Cliente $item) => PhoneNumber::normalize($item->telefono) === $telefono
        );

        $conversacion = $cliente
            ? Conversacion::whereHas('cita', fn ($cita) => $cita->where('cliente_id', $cliente->id))
                ->latest('ultimo_mensaje_at')
                ->first()
            : Conversacion::where('canal_externo_id', $telefono)
                ->latest('ultimo_mensaje_at')
                ->first();

        if (!$conversacion) {
            $conversacion = Conversacion::create([
                'asunto' => $cliente ? "WhatsApp · {$cliente->nombre}" : "WhatsApp · {$telefono}",
                'tipo' => 'whatsapp',
                'canal_externo_id' => $telefono,
                'ultimo_mensaje_at' => now(),
            ]);

            $staff = User::whereIn('rol', [User::ROL_ADMINISTRADOR, User::ROL_RECEPCION])
                ->where('activo', true)
                ->pluck('id');
            $conversacion->participantes()->sync($staff);
        } elseif ($conversacion->canal_externo_id !== $telefono) {
            $conversacion->update(['canal_externo_id' => $telefono]);
        }

        $contenido = data_get($externo, 'text.body')
            ?? '[Mensaje de WhatsApp: '.($externo['type'] ?? 'desconocido').']';

        $conversacion->mensajes()->create([
            'canal' => 'whatsapp',
            'contenido' => $contenido,
            'mensaje_externo_id' => $externoId,
            'estado' => 'recibido',
            'metadata' => ['origen' => $telefono, 'payload' => $externo],
            'enviado_at' => isset($externo['timestamp'])
                ? now()->setTimestamp((int) $externo['timestamp'])
                : now(),
        ]);

        $conversacion->update(['ultimo_mensaje_at' => now()]);
    }

    private function actualizarEstado(array $externo): void
    {
        $externoId = $externo['id'] ?? null;

        if (!$externoId) {
            return;
        }

        $mensaje = Mensaje::where('mensaje_externo_id', $externoId)->first();

        if (!$mensaje) {
            return;
        }

        $estados = [
            'sent' => 'enviado',
            'delivered' => 'entregado',
            'read' => 'leido',
            'failed' => 'fallido',
        ];
        $estadoMeta = (string) ($externo['status'] ?? 'desconocido');

        $mensaje->update([
            'estado' => $estados[$estadoMeta] ?? substr($estadoMeta, 0, 30),
            'metadata' => array_merge($mensaje->metadata ?? [], [
                'estado_meta' => $externo,
            ]),
        ]);
    }

    private function validarFirma(Request $request): void
    {
        $secret = config('services.whatsapp.app_secret');

        if (!$secret) {
            return;
        }

        $firma = (string) $request->header('X-Hub-Signature-256');
        $esperada = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        abort_unless(hash_equals($esperada, $firma), 403, 'Firma de webhook inválida.');
    }
}
