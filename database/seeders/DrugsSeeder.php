<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DrugsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('drugs')->insert([
            [
                'name'        => 'Paracetamol',
                'description' => 'Analgesico y antipiretico utilizado para tratar dolor y fiebre.',
                'type'        => 'tablet',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Ibuprofen',
                'description' => 'AINE utilizado para dolor, fiebre e inflamacion.',
                'type'        => 'tablet',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Amoxicillin',
                'description' => 'Antibiotico de amplio espectro para infecciones bacterianas.',
                'type'        => 'capsule',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Salbutamol',
                'description' => 'Broncodilatador utilizado para tratar asma y EPOC.',
                'type'        => 'other',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Omeprazole',
                'description' => 'Inhibidor de bomba de protones para reducir acido gastrico.',
                'type'        => 'capsule',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Metformin',
                'description' => 'Antidiabetico oral para el manejo de diabetes tipo 2.',
                'type'        => 'tablet',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Diphenhydramine',
                'description' => 'Antihistaminico utilizado para alergias y ayuda para dormir.',
                'type'        => 'syrup',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Insulin',
                'description' => 'Hormona utilizada para controlar la glucosa en sangre en diabetes.',
                'type'        => 'injection',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
