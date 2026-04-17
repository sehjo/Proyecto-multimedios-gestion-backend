<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiagnosesSeeder extends Seeder
{
    public function run(): void
    {
        // IDs de disease: 3=Diabetes tipo 2, 5=Asma, 4=Hipertension,
        //                 2=Influenza, 6=Infarto de miocardio
        // IDs de patient: 1=Juan, 2=Ana, 3=Roberto, 4=Sofia, 5=Luis
        // IDs de user:    2=Medico(Laura), 3=Medico(Andres)
        DB::table('diagnoses')->insert([
            [
                'name'          => 'Evaluacion inicial de diabetes',
                'disease_id'    => 3,
                'patient_id'    => 1,
                'diagnoses_by'  => 2,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Seguimiento de asma',
                'disease_id'    => 5,
                'patient_id'    => 2,
                'diagnoses_by'  => 2,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Control de hipertension',
                'disease_id'    => 4,
                'patient_id'    => 3,
                'diagnoses_by'  => 3,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Episodio agudo de influenza',
                'disease_id'    => 2,
                'patient_id'    => 4,
                'diagnoses_by'  => 3,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Valoracion cardiaca post infarto',
                'disease_id'    => 6,
                'patient_id'    => 5,
                'diagnoses_by'  => 2,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }
}
