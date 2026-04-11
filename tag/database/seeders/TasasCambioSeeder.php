<?php

namespace Database\Seeders;

use App\Models\TasaCambio;
use App\Models\Tasa;
use Illuminate\Database\Seeder;

class TasasCambioSeeder extends Seeder
{
    public function run(): void
    {
        $tasaVesBcv = Tasa::firstOrCreate(
            ['codigo' => 'VES_BCV'],
            ['nombre' => 'Bolívar BCV', 'simbolo' => 'Bs']
        );

        TasaCambio::firstOrCreate(
            [
                'fecha' => now()->toDateString(),
                'id_tasa' => $tasaVesBcv->id,
            ],
            [
                'valor_cambio' => 36.5,
            ]
        );
    }
}
