<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        // IDs de disease: 1=Resfriado comun, 2=Influenza, 3=Diabetes tipo 2,
        //                 4=Hipertension, 5=Asma, 6=Infarto de miocardio
        // IDs de user:    1=Administrador, 2=Medico(Laura), 3=Medico(Andres), 4=Enfermero(Maria)
        DB::table('patient')->insert([
            [
                'name'        => 'Juan',
                'lastname'    => 'Pérez',
                'nick'        => 'juanp',
                'suffering'   => 3,
                'register_by' => 2,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Ana',
                'lastname'    => 'Vargas',
                'nick'        => 'anav',
                'suffering'   => 5,
                'register_by' => 2,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Roberto',
                'lastname'    => 'Jiménez',
                'nick'        => 'robj',
                'suffering'   => 4,
                'register_by' => 3,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Sofía',
                'lastname'    => 'Brenes',
                'nick'        => 'sofb',
                'suffering'   => 2,
                'register_by' => 3,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Luis',
                'lastname'    => 'Castro',
                'nick'        => 'luisc',
                'suffering'   => 6,
                'register_by' => 2,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
