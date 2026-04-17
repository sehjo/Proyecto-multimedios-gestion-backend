<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users_types')->insert([
            ['name' => 'Administrador', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Medico',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enfermero',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Paciente',      'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
