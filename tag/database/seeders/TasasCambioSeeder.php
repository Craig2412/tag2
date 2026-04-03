<?php

namespace Database\Seeders;

use App\Models\TasaCambio;
use Illuminate\Database\Seeder;

class TasasCambioSeeder extends Seeder
{
    public function run(): void
    {
        $tasaUsdBase = 36.5;
        $tasaEurBase = 39.8;
        $tasaBinanceBase = 36.7;
        $porcentajePersonalizado = 2.0;
        $factor = 1 + ($porcentajePersonalizado / 100);

        TasaCambio::firstOrCreate(
            ['fecha' => now()->toDateString()],
            [
                'tasa_usd' => round($tasaUsdBase * $factor, 4),
                'tasa_eur' => round($tasaEurBase * $factor, 4),
                'tasa_binance' => round($tasaBinanceBase * $factor, 4),
                'tasa_personalizada' => $porcentajePersonalizado,
                'borrado_logico' => false,
            ]
        );
    }
}
