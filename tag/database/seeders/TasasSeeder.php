<?php

namespace Database\Seeders;

use App\Models\Tasa;
use Illuminate\Database\Seeder;

class TasasSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'BCV USD',
            'BCV EUR',
            'BINANCE',
            'PERSONALIZADA',
        ];

        foreach ($items as $tasa) {
            Tasa::firstOrCreate(['tasa' => $tasa]);
        }
    }
}
