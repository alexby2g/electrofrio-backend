<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\WhatsappIntegrationException;
use App\Http\Controllers\Controller;
use App\Services\WhatsappMetaService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class IntegracionController extends Controller
{
    public function whatsapp(WhatsAppService $whatsApp): JsonResponse
    {
        $phoneNumberId = $whatsApp->phoneNumberId();

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

    public function conectar(Request $request, WhatsappMetaService $meta): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'config_id' => ['required', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'business_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $connection = $meta->connect($validated);

            return response()->json([
                'message' => 'WhatsApp Business quedó conectado correctamente.',
                'data' => [
                    'configurado' => true,
                    'conectado' => true,
                    'status' => $connection->status,
                    'waba_id' => $connection->waba_id,
                    'phone_number_id' => $connection->phone_number_id,
                    'business_id' => $connection->business_id,
                    'numero' => $connection->display_phone_number,
                    'nombre_verificado' => $connection->verified_name,
                    'calidad' => $connection->quality_rating,
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
}
