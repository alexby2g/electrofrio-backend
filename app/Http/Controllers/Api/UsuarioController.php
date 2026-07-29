<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $usuarios = User::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    $subquery->where('name', 'like', "%{$buscar}%")
                        ->orWhere('username', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%")
                        ->orWhere('rol', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('name')
            ->with('tecnico:id,nombre')
            ->get(['id', 'name', 'username', 'email', 'telefono', 'telefono_verificado_at', 'rol', 'activo', 'tecnico_id', 'created_at', 'updated_at']);

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $request->merge(['telefono' => PhoneNumber::normalize($request->input('telefono'))]);
        $datos = $this->validar($request);
        $datos['username'] = mb_strtolower($datos['username']);
        $datos['email'] = mb_strtolower($datos['email']);
        $datos['password'] = Hash::make($datos['password']);

        $usuario = User::create($datos);

        return response()->json([
            'mensaje' => 'Usuario creado correctamente.',
            'usuario' => $usuario,
        ], 201);
    }

    public function show(User $usuario)
    {
        return response()->json($usuario);
    }

    public function update(Request $request, User $usuario)
    {
        $request->merge(['telefono' => PhoneNumber::normalize($request->input('telefono'))]);
        $datos = $this->validar($request, $usuario);
        $datos['username'] = mb_strtolower($datos['username']);
        $datos['email'] = mb_strtolower($datos['email']);
        $telefonoCambio = $datos['telefono'] !== $usuario->telefono;

        if (!empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
            $usuario->tokens()->delete();
        } else {
            unset($datos['password']);
        }

        if ($request->user()->is($usuario) && array_key_exists('activo', $datos) && !$datos['activo']) {
            return response()->json([
                'mensaje' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        if ($this->quitandoUltimoAdministrador($usuario, $datos)) {
            return response()->json([
                'mensaje' => 'Debe existir al menos un administrador activo.',
            ], 422);
        }

        $usuario->update($datos);

        if ($telefonoCambio) {
            $usuario->forceFill(['telefono_verificado_at' => null])->save();
            $usuario->tokens()->delete();
        }

        if (!$usuario->activo) {
            $usuario->tokens()->delete();
        }

        return response()->json([
            'mensaje' => 'Usuario actualizado correctamente.',
            'usuario' => $usuario->fresh(),
        ]);
    }

    public function destroy(Request $request, User $usuario)
    {
        if ($request->user()->is($usuario)) {
            return response()->json([
                'mensaje' => 'No puedes eliminar tu propia cuenta.',
            ], 422);
        }

        if ($usuario->rol === User::ROL_ADMINISTRADOR && $usuario->activo && $this->administradoresActivos() <= 1) {
            return response()->json([
                'mensaje' => 'No puedes eliminar el último administrador activo.',
            ], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json([
            'mensaje' => 'Usuario eliminado correctamente.',
        ]);
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:60',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($usuario?->id),
            ],
            'email' => [
                'required',
                'email',
                'max:160',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'telefono' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+[1-9]\d{7,14}$/',
                Rule::unique('users', 'telefono')->ignore($usuario?->id),
            ],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'rol' => ['required', Rule::in(User::ROLES)],
            'activo' => ['required', 'boolean'],
            'tecnico_id' => [
                'nullable',
                'integer',
                'exists:tecnicos,id',
                Rule::unique('users', 'tecnico_id')->ignore($usuario?->id),
            ],
        ]);
    }

    private function quitandoUltimoAdministrador(User $usuario, array $datos): bool
    {
        if ($usuario->rol !== User::ROL_ADMINISTRADOR || !$usuario->activo) {
            return false;
        }

        $seguiraActivo = (bool) ($datos['activo'] ?? $usuario->activo);
        $seguiraSiendoAdministrador = ($datos['rol'] ?? $usuario->rol) === User::ROL_ADMINISTRADOR;

        return (!$seguiraActivo || !$seguiraSiendoAdministrador) && $this->administradoresActivos() <= 1;
    }

    private function administradoresActivos(): int
    {
        return User::where('rol', User::ROL_ADMINISTRADOR)->where('activo', true)->count();
    }
}
