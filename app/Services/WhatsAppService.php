<?php

namespace App\Services;

use App\Models\WhatsappConnection;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppService
{
    public function configurado(): bool
    {
        $credentials = $this->credentials();

        return (bool) ($credentials['token'] && $credentials['phone_number_id']);
    }

    public function phoneNumberId(): string
    {
        return (string) ($this->credentials()['phone_number_id'] ?? '');
    }

    public function estadoConexion(): array
    {
        if (!$this->configurado()) {
            return [
                'configurado' => false,
                'conectado' => false,
            ];
        }

        try {
            $credentials = $this->credentials();
            $respuesta = Http::withToken($credentials['token'])
                ->timeout(8)
                ->get(
                    'https://graph.facebook.com/'
                        .config('services.whatsapp.version', 'v26.0')
                        .'/'.$credentials['phone_number_id'],
                    [
                        'fields' => 'id,display_phone_number,verified_name,quality_rating',
                    ]
                )
                ->throw()
                ->json();

            return [
                'configurado' => true,
                'conectado' => true,
                'numero' => $respuesta['display_phone_number'] ?? null,
                'nombre_verificado' => $respuesta['verified_name'] ?? null,
                'calidad' => $respuesta['quality_rating'] ?? null,
                'waba_id' => $credentials['waba_id'],
                'phone_number_id' => $credentials['phone_number_id'],
                'business_id' => $credentials['business_id'],
            ];
        } catch (Throwable) {
            return [
                'configurado' => true,
                'conectado' => false,
            ];
        }
    }

    public function enviarTexto(string $telefono, string $contenido): array
    {
        abort_unless($this->configurado(), 503, 'WhatsApp Business todavía no está configurado.');

        $credentials = $this->credentials();

        return Http::withToken($credentials['token'])
            ->post(
                'https://graph.facebook.com/'
                    .config('services.whatsapp.version', 'v26.0')
                    .'/'.$credentials['phone_number_id']
                    .'/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => ltrim($telefono, '+'),
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $contenido,
                    ],
                ]
            )
            ->throw()
            ->json();
    }

    private function credentials(): array
    {
        try {
            $connection = WhatsappConnection::query()
                ->where('status', 'connected')
                ->latest('connected_at')
                ->first();
        } catch (Throwable) {
            $connection = null;
        }

        if ($connection) {
            return [
                'token' => $connection->access_token,
                'phone_number_id' => $connection->phone_number_id,
                'waba_id' => $connection->waba_id,
                'business_id' => $connection->business_id,
            ];
        }

        return [
            'token' => config('services.whatsapp.token'),
            'phone_number_id' => config('services.whatsapp.phone_number_id'),
            'waba_id' => null,
            'business_id' => null,
        ];
    }
}
