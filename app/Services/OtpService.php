<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function solicitar(User $usuario, string $canal): array
    {
        $driver = config('services.otp.driver', 'local');

        if ($driver === 'twilio') {
            $this->enviarConTwilio($usuario->telefono, $canal);

            return ['driver' => 'twilio'];
        }

        if (app()->environment('production')) {
            throw ValidationException::withMessages([
                'telefono' => ['El servicio OTP aún no está configurado en producción.'],
            ]);
        }

        $codigo = (string) random_int(100000, 999999);

        PhoneVerification::where('user_id', $usuario->id)
            ->whereNull('usado_at')
            ->update(['usado_at' => now()]);

        PhoneVerification::create([
            'user_id' => $usuario->id,
            'telefono' => $usuario->telefono,
            'canal' => $canal,
            'codigo_hash' => Hash::make($codigo),
            'vence_at' => now()->addMinutes(10),
        ]);

        return [
            'driver' => 'local',
            'codigo_prueba' => $codigo,
        ];
    }

    public function verificar(User $usuario, string $codigo): bool
    {
        if (config('services.otp.driver', 'local') === 'twilio') {
            return $this->verificarConTwilio($usuario->telefono, $codigo);
        }

        $verificacion = PhoneVerification::where('user_id', $usuario->id)
            ->whereNull('usado_at')
            ->latest()
            ->first();

        if (!$verificacion || $verificacion->vence_at->isPast()) {
            return false;
        }

        $verificacion->increment('intentos');

        if ($verificacion->intentos > 5 || !Hash::check($codigo, $verificacion->codigo_hash)) {
            return false;
        }

        $verificacion->update(['usado_at' => now()]);

        return true;
    }

    private function enviarConTwilio(string $telefono, string $canal): void
    {
        $sid = config('services.otp.twilio.service_sid');
        $account = config('services.otp.twilio.account_sid');
        $token = config('services.otp.twilio.auth_token');

        abort_unless($sid && $account && $token, 503, 'Configura las credenciales de Twilio Verify.');

        Http::asForm()
            ->withBasicAuth($account, $token)
            ->post("https://verify.twilio.com/v2/Services/{$sid}/Verifications", [
                'To' => $telefono,
                'Channel' => $canal,
            ])
            ->throw();
    }

    private function verificarConTwilio(string $telefono, string $codigo): bool
    {
        $sid = config('services.otp.twilio.service_sid');
        $account = config('services.otp.twilio.account_sid');
        $token = config('services.otp.twilio.auth_token');

        abort_unless($sid && $account && $token, 503, 'Configura las credenciales de Twilio Verify.');

        $response = Http::asForm()
            ->withBasicAuth($account, $token)
            ->post("https://verify.twilio.com/v2/Services/{$sid}/VerificationCheck", [
                'To' => $telefono,
                'Code' => $codigo,
            ])
            ->throw()
            ->json();

        return ($response['status'] ?? null) === 'approved';
    }
}
