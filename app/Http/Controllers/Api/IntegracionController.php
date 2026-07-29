<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;

class IntegracionController extends Controller
{
    public function whatsapp(WhatsAppService $whatsApp)
    {
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');

        return response()->json(array_merge($whatsApp->estadoConexion(), [
            'webhook_configurado' => (bool) (
                config('services.whatsapp.verify_token')
                && config('services.whatsapp.app_secret')
            ),
            'version' => config('services.whatsapp.version', 'v23.0'),
            'numero_id_mascara' => $phoneNumberId
                ? '••••'.substr($phoneNumberId, -4)
                : null,
        ]));
    }
}
