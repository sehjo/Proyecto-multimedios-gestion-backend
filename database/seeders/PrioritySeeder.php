<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('priority')->insert([
            ['name' => 'Baja',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Media',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Alta',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Critica',  'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
