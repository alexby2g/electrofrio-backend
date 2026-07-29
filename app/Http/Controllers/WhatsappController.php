<?php

namespace App\Http\Controllers;

use App\Exceptions\WhatsappIntegrationException;
use App\Models\WhatsappConnection;
use App\Services\WhatsappMetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsappController extends Controller
{
    public function __construct(private readonly WhatsappMetaService $meta)
    {
    }

    public function status(): JsonResponse
    {
        $connection = WhatsappConnection::query()
            ->where('status', 'connected')
            ->latest('connected_at')
            ->first();

        if (! $connection) {
            return response()->json([
                'connected' => false,
                'status' => 'disconnected',
            ]);
        }

        return response()->json([
            'connected' => true,
            'status' => $connection->status,
            'waba_id' => $connection->waba_id,
            'phone_number_id' => $connection->phone_number_id,
            'business_id' => $connection->business_id,
            'display_phone_number' => $connection->display_phone_number,
            'verified_name' => $connection->verified_name,
            'quality_rating' => $connection->quality_rating,
            'connected_at' => optional($connection->connected_at)->toIso8601String(),
        ]);
    }

    public function embeddedSignup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'config_id' => ['required', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'business_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $connection = $this->meta->connect($validated);

            return response()->json([
                'message' => 'WhatsApp Business quedó conectado correctamente.',
                'data' => [
                    'connected' => true,
                    'status' => $connection->status,
                    'waba_id' => $connection->waba_id,
                    'phone_number_id' => $connection->phone_number_id,
                    'business_id' => $connection->business_id,
                    'display_phone_number' => $connection->display_phone_number,
                    'verified_name' => $connection->verified_name,
                    'quality_rating' => $connection->quality_rating,
                ],
            ]);
        } catch (WhatsappIntegrationException $exception) {
            Log::warning('No se pudo completar WhatsApp Embedded Signup', [
                'message' => $exception->getMessage(),
                'config_id' => $validated['config_id'],
                'waba_id' => $validated['waba_id'] ?? null,
                'phone_number_id' => $validated['phone_number_id'] ?? null,
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo completar la conexión con Meta.',
            ], 500);
        }
    }

    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        $expectedToken = $this->meta->webhookVerifyToken();

        if (
            $mode === 'subscribe'
            && $expectedToken !== ''
            && is_string($token)
            && hash_equals($expectedToken, $token)
        ) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['message' => 'Verificación de webhook rechazada.'], 403);
    }

    public function receiveWebhook(Request $request): JsonResponse
    {
        if (! $this->meta->hasValidWebhookSignature($request)) {
            return response()->json(['message' => 'Firma del webhook no válida.'], 401);
        }

        $payload = $request->json()->all();
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $wabaId = $entry['id'] ?? null;

            if ($wabaId) {
                WhatsappConnection::query()
                    ->where('waba_id', (string) $wabaId)
                    ->update(['last_verified_at' => now()]);
            }
        }

        Log::info('Webhook de WhatsApp recibido', [
            'object' => $payload['object'] ?? null,
            'entries' => is_array($entries) ? count($entries) : 0,
        ]);

        return response()->json(['received' => true]);
    }
}
