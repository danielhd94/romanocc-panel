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

        // Crear usuario abogado
        User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::LAWYER,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario estudiante
        User::create([
            'name' => 'María García',
            'email' => 'maria.garcia@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::STUDENT,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario profesor
        User::create([
            'name' => 'Dr. Carlos López',
            'email' => 'carlos.lopez@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::PROFESSOR,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario investigador
        User::create([
            'name' => 'Ana Rodríguez',
            'email' => 'ana.rodriguez@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::RESEARCHER,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario público
        User::create([
            'name' => 'Pedro Martínez',
            'email' => 'pedro.martinez@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::PUBLIC,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario inactivo
        User::create([
            'name' => 'Usuario Inactivo',
            'email' => 'inactivo@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::PUBLIC,
            'status' => UserStatus::INACTIVE,
            'email_verified_at' => now(),
        ]);

        // Crear usuario suspendido
        User::create([
            'name' => 'Usuario Suspendido',
            'email' => 'suspendido@ejemplo.com',
            'password' => Hash::make('password'),
            'type' => UserType::PUBLIC,
            'status' => UserStatus::SUSPENDED,
            'email_verified_at' => now(),
        ]);
    }
}
