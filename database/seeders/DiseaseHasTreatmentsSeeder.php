<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiseaseHasTreatmentsSeeder extends Seeder
{
    public function run(): void
    {
        // IDs de disease: 1=Resfriado comun, 2=Influenza, 3=Diabetes tipo 2,
        //                 4=Hipertension, 5=Asma, 6=Infarto de miocardio
        // IDs de drug:    1=Paracetamol, 2=Ibuprofen, 3=Amoxicillin,
        //                 4=Salbutamol, 5=Omeprazole, 6=Metformin,
        //                 7=Diphenhydramine, 8=Insulin
        DB::table('disease_has_treatments')->insert([
            // Resfriado comun
            ['disease_id' => 1, 'drugs' => 1, 'descriptions' => 'Paracetamol 500mg cada 8h para reducir fiebre y dolor.', 'created_at' => now(), 'updated_at' => now()],
            ['disease_id' => 1, 'drugs' => 7, 'descriptions' => 'Diphenhydramine para aliviar congestion nasal y sintomas alergicos.', 'created_at' => now(), 'updated_at' => now()],

            // Influenza
            ['disease_id' => 2, 'drugs' => 1, 'descriptions' => 'Paracetamol 500mg cada 6h para manejo de fiebre.', 'created_at' => now(), 'updated_at' => now()],
            ['disease_id' => 2, 'drugs' => 2, 'descriptions' => 'Ibuprofen 400mg cada 8h para dolor corporal.', 'created_at' => now(), 'updated_at' => now()],

            // Diabetes tipo 2
            ['disease_id' => 3, 'drugs' => 6, 'descriptions' => 'Metformin 850mg dos veces al dia con comidas.', 'created_at' => now(), 'updated_at' => now()],
            ['disease_id' => 3, 'drugs' => 8, 'descriptions' => 'Insulin segun necesidad con base en monitoreo de glucosa.', 'created_at' => now(), 'updated_at' => now()],

            // Hipertension
            ['disease_id' => 4, 'drugs' => 2, 'descriptions' => 'Ibuprofen evitado; documentar como tratamiento contraindicado.', 'created_at' => now(), 'updated_at' => now()],

            // Asma
            ['disease_id' => 5, 'drugs' => 4, 'descriptions' => 'Inhalador de Salbutamol 2 puffs segun necesidad durante crisis.', 'created_at' => now(), 'updated_at' => now()],

            // Infarto de miocardio
            ['disease_id' => 6, 'drugs' => 2, 'descriptions' => 'Aspirina en dosis baja (grupo Ibuprofen) para terapia antiplaquetaria.', 'created_at' => now(), 'updated_at' => now()],
            ['disease_id' => 6, 'drugs' => 5, 'descriptions' => 'Omeprazole 20mg para proteger mucosa gastrica con antiplaquetarios.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
