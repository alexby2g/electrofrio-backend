<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'identificador' => ['nullable', 'string', 'max:160', 'required_without:email'],
            'email' => ['nullable', 'email', 'required_without:identificador'],
            'password' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:120'],
        ]);

        $identificador = trim((string) ($credenciales['identificador'] ?? $credenciales['email'] ?? ''));
        $identificadorNormalizado = mb_strtolower($identificador);
        if (str_contains($identificador, '@')) {
            $usuario = User::where('email', $identificadorNormalizado)->first();
        } else {
            $usuario = User::where('username', $identificadorNormalizado)->first();

            if (!$usuario) {
                $usuario = User::where('telefono', PhoneNumber::normalize($identificador))->first();
            }
        }

        if (!$usuario || !Hash::check($credenciales['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'identificador' => ['El usuario, correo, teléfono o la contraseña no son correctos.'],
            ]);
        }

        if (!$usuario->activo) {
            throw ValidationException::withMessages([
                'identificador' => ['Este usuario está desactivado. Comunícate con el administrador.'],
            ]);
        }

        $usuario->tokens()->where('name', $credenciales['dispositivo'] ?? 'electrofrio-app')->delete();
        $token = $usuario->createToken($credenciales['dispositivo'] ?? 'electrofrio-app')->plainTextToken;

        return response()->json([
            'mensaje' => 'Sesión iniciada correctamente.',
            'token' => $token,
            'usuario' => $this->usuarioParaRespuesta($usuario),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'usuario' => $this->usuarioParaRespuesta($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    private function usuarioParaRespuesta(User $usuario): array
    {
        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'username' => $usuario->username,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
            'telefono_verificado_at' => $usuario->telefono_verificado_at,
            'rol' => $usuario->rol,
            'activo' => (bool) $usuario->activo,
            'tecnico_id' => $usuario->tecnico_id,
        ];
    }
}
