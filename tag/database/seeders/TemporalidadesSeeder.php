<?php

namespace Database\Seeders;

use App\Models\Temporalidad;
use Illuminate\Database\Seeder;

class TemporalidadesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['temporalidad' => 'Diario', 'slug' => 'diario', 'carbon_method' => 'startOfDay'],
            ['temporalidad' => 'Semanal', 'slug' => 'semanal', 'carbon_method' => 'startOfWeek'],
            ['temporalidad' => 'Mensual', 'slug' => 'mensual', 'carbon_method' => 'startOfMonth'],
            ['temporalidad' => 'Anual', 'slug' => 'anual', 'carbon_method' => 'startOfYear'],
        ];

        foreach ($items as $item) {
            Temporalidad::firstOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
