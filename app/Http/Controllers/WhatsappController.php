<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WhatsappController extends Controller
{
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

        $appId = (string) config('services.meta_whatsapp.app_id');
        $appSecret = (string) config('services.meta_whatsapp.app_secret');
        $configuredConfigId = (string) config('services.meta_whatsapp.config_id');

        if ($appId === '' || $appSecret === '') {
            return response()->json([
                'message' => 'La integración de Meta todavía no tiene APP ID y APP SECRET configurados en el servidor.',
            ], 503);
        }

        if ($configuredConfigId !== '' && ! hash_equals($configuredConfigId, $validated['config_id'])) {
            return response()->json([
                'message' => 'El identificador de configuración de Meta no corresponde a Electro Frío.',
            ], 422);
        }

        try {
            $tokenData = $this->exchangeCodeForToken($validated['code'], $appId, $appSecret);
            $accessToken = $tokenData['access_token'];
            $debugData = $this->debugToken($accessToken, $appId, $appSecret);

            $wabaId = $validated['waba_id'] ?: $this->extractWabaId($debugData);

            if (! $wabaId) {
                throw new RuntimeException('Meta autorizó la cuenta, pero no devolvió el identificador de WhatsApp Business.');
            }

            $phone = $this->resolvePhoneNumber(
                $wabaId,
                $validated['phone_number_id'] ?? null,
                $accessToken,
                $appSecret
            );

            $this->subscribeAppToWaba($wabaId, $accessToken, $appSecret);

            $expiresAt = null;
            if (! empty($tokenData['expires_in'])) {
                $expiresAt = now()->addSeconds((int) $tokenData['expires_in']);
            } elseif (! empty($debugData['expires_at'])) {
                $expiresAt = now()->setTimestamp((int) $debugData['expires_at']);
            }

            $connection = DB::transaction(function () use (
                $validated,
                $wabaId,
                $phone,
                $accessToken,
                $tokenData,
                $expiresAt,
                $debugData
            ) {
                WhatsappConnection::query()
                    ->where('phone_number_id', '!=', $phone['id'])
                    ->where('status', 'connected')
                    ->update(['status' => 'disconnected']);

                return WhatsappConnection::updateOrCreate(
                    ['phone_number_id' => $phone['id']],
                    [
                        'config_id' => $validated['config_id'],
                        'business_id' => $validated['business_id'] ?? null,
                        'waba_id' => $wabaId,
                        'display_phone_number' => $phone['display_phone_number'] ?? null,
                        'verified_name' => $phone['verified_name'] ?? null,
                        'quality_rating' => $phone['quality_rating'] ?? null,
                        'access_token' => $accessToken,
                        'token_type' => $tokenData['token_type'] ?? 'bearer',
                        'token_expires_at' => $expiresAt,
                        'status' => 'connected',
                        'metadata' => [
                            'phone' => $phone,
                            'token_user_id' => $debugData['user_id'] ?? null,
                            'data_access_expires_at' => $debugData['data_access_expires_at'] ?? null,
                        ],
                        'connected_at' => now(),
                        'last_verified_at' => now(),
                    ]
                );
            });

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
        } catch (Throwable $exception) {
            Log::warning('No se pudo completar WhatsApp Embedded Signup', [
                'message' => $exception->getMessage(),
                'config_id' => $validated['config_id'],
                'waba_id' => $validated['waba_id'] ?? null,
                'phone_number_id' => $validated['phone_number_id'] ?? null,
            ]);

            return response()->json([
                'message' => $exception->getMessage() ?: 'No se pudo completar la conexión con Meta.',
            ], 422);
        }
    }

    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        $expectedToken = (string) config('services.meta_whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $expectedToken !== '' && is_string($token) && hash_equals($expectedToken, $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['message' => 'Verificación de webhook rechazada.'], 403);
    }

    public function receiveWebhook(Request $request): JsonResponse
    {
        if (! $this->hasValidWebhookSignature($request)) {
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

    private function exchangeCodeForToken(string $code, string $appId, string $appSecret): array
    {
        $response = Http::acceptJson()
            ->timeout(20)
            ->retry(2, 300)
            ->get($this->graphUrl('oauth/access_token'), [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'code' => $code,
            ]);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException($this->graphError($response, 'Meta rechazó el código de autorización.'));
        }

        return $response->json();
    }

    private function debugToken(string $accessToken, string $appId, string $appSecret): array
    {
        $response = Http::acceptJson()
            ->timeout(20)
            ->retry(2, 300)
            ->get($this->graphUrl('debug_token'), [
                'input_token' => $accessToken,
                'access_token' => $appId.'|'.$appSecret,
            ]);

        if ($response->failed() || $response->json('data.is_valid') !== true) {
            throw new RuntimeException($this->graphError($response, 'Meta devolvió un token de acceso no válido.'));
        }

        return $response->json('data', []);
    }

    private function extractWabaId(array $debugData): ?string
    {
        foreach ($debugData['granular_scopes'] ?? [] as $scope) {
            if (! in_array($scope['scope'] ?? null, [
                'whatsapp_business_management',
                'whatsapp_business_messaging',
            ], true)) {
                continue;
            }

            $targetId = $scope['target_ids'][0] ?? null;
            if ($targetId) {
                return (string) $targetId;
            }
        }

        return null;
    }

    private function resolvePhoneNumber(
        string $wabaId,
        ?string $requestedPhoneNumberId,
        string $accessToken,
        string $appSecret
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 300)
            ->get($this->graphUrl($wabaId.'/phone_numbers'), [
                'fields' => 'id,display_phone_number,verified_name,quality_rating',
                'appsecret_proof' => $this->appSecretProof($accessToken, $appSecret),
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->graphError($response, 'No se pudieron consultar los números de WhatsApp autorizados.'));
        }

        $phones = $response->json('data', []);

        if ($requestedPhoneNumberId) {
            foreach ($phones as $phone) {
                if ((string) ($phone['id'] ?? '') === $requestedPhoneNumberId) {
                    return $phone;
                }
            }

            throw new RuntimeException('El número autorizado no pertenece a la cuenta de WhatsApp Business seleccionada.');
        }

        if (! empty($phones[0]['id'])) {
            return $phones[0];
        }

        throw new RuntimeException('La cuenta autorizada no contiene un número de WhatsApp disponible.');
    }

    private function subscribeAppToWaba(string $wabaId, string $accessToken, string $appSecret): void
    {
        $response = Http::withToken($accessToken)
            ->asForm()
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 300)
            ->post($this->graphUrl($wabaId.'/subscribed_apps'), [
                'appsecret_proof' => $this->appSecretProof($accessToken, $appSecret),
            ]);

        if ($response->failed() || $response->json('success') !== true) {
            throw new RuntimeException($this->graphError($response, 'Meta no pudo suscribir la aplicación a la cuenta de WhatsApp Business.'));
        }
    }

    private function hasValidWebhookSignature(Request $request): bool
    {
        $appSecret = (string) config('services.meta_whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($appSecret === '' || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }

    private function appSecretProof(string $accessToken, string $appSecret): string
    {
        return hash_hmac('sha256', $accessToken, $appSecret);
    }

    private function graphUrl(string $path): string
    {
        $version = trim((string) config('services.meta_whatsapp.graph_version', 'v26.0'), '/');

        return 'https://graph.facebook.com/'.$version.'/'.ltrim($path, '/');
    }

    private function graphError(Response $response, string $fallback): string
    {
        return (string) ($response->json('error.message') ?: $fallback);
    }
}
