<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiseaseSeeder extends Seeder
{
    public function run(): void
    {
        // IDs de priority: 1=Baja, 2=Media, 3=Alta, 4=Critica
        DB::table('disease')->insert([
            [
                'name'           => 'Resfriado comun',
                'technincal_name'=> 'Infeccion por rinovirus',
                'description'    => 'Infeccion viral del tracto respiratorio superior.',
                'priority_id'    => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Influenza',
                'technincal_name'=> 'Virus de la influenza',
                'description'    => 'Enfermedad respiratoria altamente contagiosa causada por virus de influenza.',
                'priority_id'    => 2,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Diabetes tipo 2',
                'technincal_name'=> 'Diabetes mellitus tipo 2',
                'description'    => 'Condicion cronica que afecta el metabolismo de la glucosa.',
                'priority_id'    => 3,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Hipertension',
                'technincal_name'=> 'Hipertension arterial',
                'description'    => 'Presion arterial persistentemente elevada en las arterias.',
                'priority_id'    => 3,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Asthma',
                'technincal_name'=> 'Asma bronquial',
                'description'    => 'Condicion inflamatoria cronica de las vias respiratorias.',
                'priority_id'    => 2,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Infarto de miocardio',
                'technincal_name'=> 'IAM agudo',
                'description'    => 'Bloqueo del flujo sanguineo hacia una parte del musculo cardiaco.',
                'priority_id'    => 4,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
