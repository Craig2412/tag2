<?php

namespace Database\Seeders;

use App\Models\TasaCambio;
use Illuminate\Database\Seeder;

class TasasCambioSeeder extends Seeder
{
    public function run(): void
    {
        TasaCambio::firstOrCreate(
            ['fecha' => now()->toDateString()],
            [
                'tasa_usd' => 36.5,
                'tasa_eur' => 39.8,
                'tasa_binance' => 36.7,
                'tasa_personalizada' => 36.6,
                'borrado_logico' => false,
            ]
        );
    }
}
