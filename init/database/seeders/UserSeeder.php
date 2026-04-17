<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // IDs de user_type: 1=Administrador, 2=Medico, 3=Enfermero, 4=Paciente
        DB::table('users')->insert([
            [
                'name'         => 'Carlos',
                'lastname'     => 'Ramírez',
                'email'        => 'admin@ccss.cr',
                'password'     => Hash::make('Admin1234!'),
                'user_type_id' => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'Laura',
                'lastname'     => 'Soto',
                'email'        => 'doctor1@ccss.cr',
                'password'     => Hash::make('Doctor1234!'),
                'user_type_id' => 2,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'Andrés',
                'lastname'     => 'Mora',
                'email'        => 'doctor2@ccss.cr',
                'password'     => Hash::make('Doctor1234!'),
                'user_type_id' => 2,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'María',
                'lastname'     => 'González',
                'email'        => 'nurse1@ccss.cr',
                'password'     => Hash::make('Nurse1234!'),
                'user_type_id' => 3,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}
