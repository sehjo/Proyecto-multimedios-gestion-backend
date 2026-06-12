<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Carlos', 'Ramírez',  'admin@ccss.cr',   'Admin1234!',  'Administrador'],
            ['Laura',  'Soto',     'doctor1@ccss.cr', 'Doctor1234!', 'Medico'],
            ['Andrés', 'Mora',     'doctor2@ccss.cr', 'Doctor1234!', 'Medico'],
            ['María',  'González', 'nurse1@ccss.cr',  'Nurse1234!',  'Enfermero'],
        ];

        foreach ($users as [$name, $lastname, $email, $password, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $name,
                    'lastname' => $lastname,
                    'password' => Hash::make($password),
                ]
            );

            $user->syncRoles([$role]);
        }
    }
}
