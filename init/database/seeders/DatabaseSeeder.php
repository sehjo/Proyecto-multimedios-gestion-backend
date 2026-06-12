<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Order matters because of the foreign key constraints.
     */
    public function run(): void
    {
        $this->call([
            // Roles and permissions (Spatie). Must run before UserSeeder.
            RolePermissionSeeder::class,

            // Catalogs (no FK dependencies)
            PrioritySeeder::class,
            DrugsSeeder::class,

            // Depends on priority
            DiseaseSeeder::class,

            // Depends on disease + drugs
            DiseaseHasTreatmentsSeeder::class,

            // Depends on the roles created by RolePermissionSeeder
            UserSeeder::class,

            // Depends on disease + users
            PatientSeeder::class,

            // Depends on disease + patient + users
            DiagnosesSeeder::class,

            // Depends on diagnoses + drugs
            DiagnosesHasTreatmentsSeeder::class,
        ]);
    }
}
