<?php

namespace Database\Seeders;

use App\Models\Tasa;
use Illuminate\Database\Seeder;

class TasasSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['codigo' => 'USD_BCV', 'nombre' => 'Dólar BCV', 'simbolo' => '$'],
            ['codigo' => 'EUR_BCV', 'nombre' => 'Euro Oficial', 'simbolo' => '€'],
            ['codigo' => 'BINANCE', 'nombre' => 'Binance P2P', 'simbolo' => 'USDT'],
            ['codigo' => 'PERSONALIZADA', 'nombre' => 'Tasa Interna', 'simbolo' => '$'],
        ];

        foreach ($items as $item) {
            Tasa::firstOrCreate(
                ['codigo' => $item['codigo']],
                [
                    'nombre' => $item['nombre'],
                    'simbolo' => $item['simbolo'],
                ]
            );
        }
    }
}
