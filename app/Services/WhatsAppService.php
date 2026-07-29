<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppService
{
    public function configurado(): bool
    {
        return (bool) (
            config('services.whatsapp.token')
            && config('services.whatsapp.phone_number_id')
        );
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
            $respuesta = Http::withToken(config('services.whatsapp.token'))
                ->timeout(8)
                ->get(
                    'https://graph.facebook.com/'
                        .config('services.whatsapp.version', 'v23.0')
                        .'/'.config('services.whatsapp.phone_number_id'),
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

        return Http::withToken(config('services.whatsapp.token'))
            ->post(
                'https://graph.facebook.com/'
                    .config('services.whatsapp.version', 'v23.0')
                    .'/'.config('services.whatsapp.phone_number_id')
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
}
