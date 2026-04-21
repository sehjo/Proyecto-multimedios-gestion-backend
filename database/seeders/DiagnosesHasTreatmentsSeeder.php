<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiagnosesHasTreatmentsSeeder extends Seeder
{
    public function run(): void
    {
        // IDs de diagnoses: 1=eval diabetes, 2=seguimiento asma,
        //                   3=control hipertension, 4=influenza, 5=post infarto
        // IDs de drug:      1=Paracetamol, 2=Ibuprofen, 4=Salbutamol,
        //                   5=Omeprazole, 6=Metformin, 8=Insulin
        DB::table('diagnoses_has_treatments')->insert([
            // Diabetes
            ['diagnoses_id' => 1, 'drugs' => 6, 'descriptions' => 'Metformin 850mg dos veces al dia con comidas para control glucemico.', 'created_at' => now(), 'updated_at' => now()],
            ['diagnoses_id' => 1, 'drugs' => 8, 'descriptions' => 'Insulin 10 UI subcutanea al acostarse.', 'created_at' => now(), 'updated_at' => now()],

            // Asma
            ['diagnoses_id' => 2, 'drugs' => 4, 'descriptions' => 'Salbutamol 2 puffs cada 4-6h segun necesidad por broncoespasmo.', 'created_at' => now(), 'updated_at' => now()],

            // Hipertension
            ['diagnoses_id' => 3, 'drugs' => 5, 'descriptions' => 'Omeprazole 20mg diario como proteccion gastrica.', 'created_at' => now(), 'updated_at' => now()],

            // Influenza
            ['diagnoses_id' => 4, 'drugs' => 1, 'descriptions' => 'Paracetamol 500mg cada 6h para fiebre y malestar.', 'created_at' => now(), 'updated_at' => now()],
            ['diagnoses_id' => 4, 'drugs' => 2, 'descriptions' => 'Ibuprofen 400mg cada 8h para aliviar dolor muscular.', 'created_at' => now(), 'updated_at' => now()],

            // Post infarto
            ['diagnoses_id' => 5, 'drugs' => 2, 'descriptions' => 'Ibuprofen dosis baja 100mg diario como terapia antiplaquetaria.', 'created_at' => now(), 'updated_at' => now()],
            ['diagnoses_id' => 5, 'drugs' => 5, 'descriptions' => 'Omeprazole 20mg diario para proteger mucosa gastrica.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
