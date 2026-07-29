<?php

namespace Database\Seeders;

use App\Support\PhoneNumber;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $esProduccion = app()->environment('production');
        $email = env('ADMIN_EMAIL', $esProduccion ? null : 'alexv2g@gmail.com');
        $password = env('ADMIN_PASSWORD');
        $username = mb_strtolower(trim((string) env('ADMIN_USERNAME', 'alexv2g')));

        if (!$email || !$password) {
            $this->command?->warn('No se creó el administrador: configura ADMIN_EMAIL y ADMIN_PASSWORD de forma privada.');
            return;
        }

        User::updateOrCreate(
            ['email' => mb_strtolower($email)],
            [
                'name' => env('ADMIN_NAME', 'Administrador Electro Frío'),
                'username' => $username ?: null,
                'telefono' => PhoneNumber::normalize(env('ADMIN_PHONE')),
                'password' => Hash::make($password),
                'rol' => User::ROL_ADMINISTRADOR,
                'activo' => true,
            ]
        );
    }
}
