<?php

namespace App\Services;

use App\Exceptions\WhatsappIntegrationException;
use App\Models\WhatsappConnection;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsappMetaService
{
    private string $appId;
    private string $appSecret;
    private string $configId;
    private string $graphVersion;
    private string $webhookVerifyToken;

    public function __construct()
    {
        $this->appId = (string) config('services.meta_whatsapp.app_id');
        $this->appSecret = (string) config('services.meta_whatsapp.app_secret');
        $this->configId = (string) config('services.meta_whatsapp.config_id');
        $this->graphVersion = trim((string) config('services.meta_whatsapp.graph_version', 'v26.0'), '/');
        $this->webhookVerifyToken = (string) config('services.meta_whatsapp.webhook_verify_token');
    }

    public function connect(array $payload): WhatsappConnection
    {
        $this->assertConfigured($payload['config_id']);

        try {
            $tokenData = $this->exchangeCodeForToken($payload['code']);
            $accessToken = (string) $tokenData['access_token'];
            $debugData = $this->debugToken($accessToken);

            $wabaId = $payload['waba_id'] ?: $this->extractWabaId($debugData);

            if (! $wabaId) {
                throw new WhatsappIntegrationException(
                    'Meta autorizó la cuenta, pero no devolvió el identificador de WhatsApp Business.'
                );
            }

            $phone = $this->resolvePhoneNumber(
                (string) $wabaId,
                $payload['phone_number_id'] ?? null,
                $accessToken
            );

            $this->subscribeAppToWaba((string) $wabaId, $accessToken);

            $expiresAt = $this->resolveTokenExpiration($tokenData, $debugData);

            return DB::transaction(function () use (
                $payload,
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
                    ['phone_number_id' => (string) $phone['id']],
                    [
                        'config_id' => $payload['config_id'],
                        'business_id' => $payload['business_id'] ?? null,
                        'waba_id' => (string) $wabaId,
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
        } catch (WhatsappIntegrationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw new WhatsappIntegrationException(
                'No se pudo comunicar con Meta. Intenta conectar nuevamente en unos minutos.'
            );
        }
    }

    public function webhookVerifyToken(): string
    {
        return $this->webhookVerifyToken;
    }

    public function hasValidWebhookSignature(Request $request): bool
    {
        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($this->appSecret === '' || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $this->appSecret);

        return hash_equals($expected, $signature);
    }

    private function assertConfigured(string $incomingConfigId): void
    {
        if ($this->appId === '' || $this->appSecret === '') {
            throw new WhatsappIntegrationException(
                'La integración de Meta todavía no tiene APP ID y APP SECRET configurados en el servidor.'
            );
        }

        if ($this->configId !== '' && ! hash_equals($this->configId, $incomingConfigId)) {
            throw new WhatsappIntegrationException(
                'El identificador de configuración de Meta no corresponde a Electro Frío.'
            );
        }
    }

    private function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->timeout(20)
            ->post($this->graphUrl('oauth/access_token'), [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'code' => $code,
            ]);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new WhatsappIntegrationException(
                $this->graphError($response, 'Meta rechazó el código de autorización.')
            );
        }

        return $response->json();
    }

    private function debugToken(string $accessToken): array
    {
        $response = Http::withToken($this->appId.'|'.$this->appSecret)
            ->acceptJson()
            ->timeout(20)
            ->get($this->graphUrl('debug_token'), [
                'input_token' => $accessToken,
            ]);

        if ($response->failed() || $response->json('data.is_valid') !== true) {
            throw new WhatsappIntegrationException(
                $this->graphError($response, 'Meta devolvió un token de acceso no válido.')
            );
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
        string $accessToken
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->get($this->graphUrl($wabaId.'/phone_numbers'), [
                'fields' => 'id,display_phone_number,verified_name,quality_rating',
                'appsecret_proof' => $this->appSecretProof($accessToken),
            ]);

        if ($response->failed()) {
            throw new WhatsappIntegrationException(
                $this->graphError($response, 'No se pudieron consultar los números de WhatsApp autorizados.')
            );
        }

        $phones = $response->json('data', []);

        if ($requestedPhoneNumberId) {
            foreach ($phones as $phone) {
                if ((string) ($phone['id'] ?? '') === $requestedPhoneNumberId) {
                    return $phone;
                }
            }

            throw new WhatsappIntegrationException(
                'El número autorizado no pertenece a la cuenta de WhatsApp Business seleccionada.'
            );
        }

        if (! empty($phones[0]['id'])) {
            return $phones[0];
        }

        throw new WhatsappIntegrationException(
            'La cuenta autorizada no contiene un número de WhatsApp disponible.'
        );
    }

    private function subscribeAppToWaba(string $wabaId, string $accessToken): void
    {
        $response = Http::withToken($accessToken)
            ->asForm()
            ->acceptJson()
            ->timeout(20)
            ->post($this->graphUrl($wabaId.'/subscribed_apps'), [
                'appsecret_proof' => $this->appSecretProof($accessToken),
            ]);

        if ($response->failed() || $response->json('success') !== true) {
            throw new WhatsappIntegrationException(
                $this->graphError(
                    $response,
                    'Meta no pudo suscribir la aplicación a la cuenta de WhatsApp Business.'
                )
            );
        }
    }

    private function resolveTokenExpiration(array $tokenData, array $debugData): ?Carbon
    {
        if (! empty($tokenData['expires_in'])) {
            return now()->addSeconds((int) $tokenData['expires_in']);
        }

        if (! empty($debugData['expires_at'])) {
            return Carbon::createFromTimestamp((int) $debugData['expires_at']);
        }

        return null;
    }

    private function appSecretProof(string $accessToken): string
    {
        return hash_hmac('sha256', $accessToken, $this->appSecret);
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion.'/'.ltrim($path, '/');
    }

    private function graphError(Response $response, string $fallback): string
    {
        return (string) ($response->json('error.message') ?: $fallback);
    }
}
