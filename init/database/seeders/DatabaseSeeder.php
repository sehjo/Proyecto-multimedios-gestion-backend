<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Poblar la base de datos de la aplicacion.
     * El orden importa por las restricciones de llaves foraneas.
     */
    public function run(): void
    {
        $this->call([
            // Catalogos (sin dependencias de FK)
            UserTypeSeeder::class,
            PrioritySeeder::class,
            DrugsSeeder::class,

            // Depende de priority
            DiseaseSeeder::class,

            // Depende de disease + drugs
            DiseaseHasTreatmentsSeeder::class,

            // Depende de users_types
            UserSeeder::class,

            // Depende de disease + users
            PatientSeeder::class,

            // Depende de disease + patient + users
            DiagnosesSeeder::class,

            // Depende de diagnoses + drugs
            DiagnosesHasTreatmentsSeeder::class,
        ]);
    }
}
