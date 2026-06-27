<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@romanoc.com',
            'password' => Hash::make('password'),
            'type' => UserType::ADMIN,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario público
        User::create([
            'name' => 'Usuario Público',
            'email' => 'usuario.publico@ejemplo.com',
            'email_verified_at' => now(),
            'phone' => '1234567890',
            'password' => Hash::make('password'),
            'type' => UserType::PUBLIC,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario inactivo
        User::create([
            'name' => 'Usuario Inactivo',
            'email' => 'usuario.inactivo@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::PUBLIC,
            'status' => UserStatus::INACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario suspendido
        User::create([
            'name' => 'Usuario Suspendido',
            'email' => 'usuario.suspendido@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::PUBLIC,
            'status' => UserStatus::SUSPENDED,
            'email_verified_at' => now(),
        ]);
    }
}
