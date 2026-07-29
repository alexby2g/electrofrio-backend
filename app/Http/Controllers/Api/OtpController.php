<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function solicitar(Request $request, OtpService $otp)
    {
        $datos = $request->validate([
            'telefono' => ['required', 'string', 'max:30'],
            'canal' => ['required', Rule::in(['sms', 'whatsapp'])],
        ]);

        $telefono = PhoneNumber::normalize($datos['telefono']);
        $usuario = User::where('telefono', $telefono)->where('activo', true)->first();

        if (!$usuario) {
            throw ValidationException::withMessages([
                'telefono' => ['No existe un usuario activo con ese número.'],
            ]);
        }

        $resultado = $otp->solicitar($usuario, $datos['canal']);

        return response()->json([
            'mensaje' => 'Código enviado correctamente.',
            'vence_en_minutos' => 10,
            'codigo_prueba' => $resultado['codigo_prueba'] ?? null,
        ]);
    }

    public function verificar(Request $request, OtpService $otp)
    {
        $datos = $request->validate([
            'telefono' => ['required', 'string', 'max:30'],
            'codigo' => ['required', 'string', 'min:4', 'max:10'],
            'dispositivo' => ['nullable', 'string', 'max:120'],
        ]);

        $telefono = PhoneNumber::normalize($datos['telefono']);
        $usuario = User::where('telefono', $telefono)->where('activo', true)->first();

        if (!$usuario || !$otp->verificar($usuario, $datos['codigo'])) {
            throw ValidationException::withMessages([
                'codigo' => ['El código es incorrecto o ya venció.'],
            ]);
        }

        $usuario->forceFill(['telefono_verificado_at' => now()])->save();
        $dispositivo = $datos['dispositivo'] ?? 'electrofrio-otp';
        $usuario->tokens()->where('name', $dispositivo)->delete();
        $token = $usuario->createToken($dispositivo)->plainTextToken;

        return response()->json([
            'mensaje' => 'Teléfono verificado. Sesión iniciada.',
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'telefono_verificado_at' => $usuario->telefono_verificado_at,
                'rol' => $usuario->rol,
                'activo' => (bool) $usuario->activo,
                'tecnico_id' => $usuario->tecnico_id,
            ],
        ]);
    }
}
