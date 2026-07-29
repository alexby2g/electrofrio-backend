<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_active_user_can_login_and_receive_token(): void
    {
        $usuario = User::factory()->create([
            'username' => 'alexv2g',
            'email' => 'admin@electrofrio.bo',
            'password' => 'ClaveSegura123!',
            'rol' => User::ROL_ADMINISTRADOR,
            'activo' => true,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $usuario->email,
            'password' => 'ClaveSegura123!',
            'dispositivo' => 'pruebas',
        ])->assertOk()
            ->assertJsonPath('usuario.rol', User::ROL_ADMINISTRADOR)
            ->assertJsonStructure(['token', 'usuario' => ['id', 'name', 'email', 'rol', 'activo']]);
    }

    public function test_active_user_can_login_with_username(): void
    {
        User::factory()->create([
            'username' => 'alexv2g',
            'password' => 'ClaveSegura123!',
            'activo' => true,
        ]);

        $this->postJson('/api/auth/login', [
            'identificador' => 'alexv2g',
            'password' => 'ClaveSegura123!',
        ])->assertOk()
            ->assertJsonPath('usuario.username', 'alexv2g');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $usuario = User::factory()->create([
            'password' => 'ClaveSegura123!',
            'activo' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $usuario->email,
            'password' => 'ClaveSegura123!',
        ])->assertUnprocessable();
    }

    public function test_only_administrator_can_list_users(): void
    {
        $recepcion = User::factory()->create([
            'rol' => User::ROL_RECEPCION,
            'activo' => true,
        ]);

        Sanctum::actingAs($recepcion);
        $this->getJson('/api/usuarios')->assertForbidden();

        $administrador = User::factory()->create([
            'rol' => User::ROL_ADMINISTRADOR,
            'activo' => true,
        ]);

        Sanctum::actingAs($administrador);
        $this->getJson('/api/usuarios')->assertOk();
    }
}
