<?php

namespace Database\Seeders;

// Asegúrate de importar Hash para encriptar la contraseña
use Illuminate\Support\Facades\Hash; 
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Llamamos al Seeder de ejercicios (para que no se borren)
        $this->call([
            EjerciciosSeeder::class,
        ]);
        DB::table('users')->where('id', 1)->delete();
        // 2. Creamos TU usuario administrador
        User::factory()->create([
            'id'       => 1,
            'nombre' => 'Alumno Test',
            'email' => 'alumno@test.com',
            'contrasena_hash' => Hash::make('12345678'),
            'rol' => 'alumno',
        ]);
    }
}