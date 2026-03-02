<?php

namespace Database\Seeders;

use App\Models\Temporalidad;
use Illuminate\Database\Seeder;

class TemporalidadesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Diario',
            'Semanal',
            'Mensual',
            'Anual',
        ];

        foreach ($items as $temporalidad) {
            Temporalidad::firstOrCreate(['temporalidad' => $temporalidad]);
        }
    }
}
