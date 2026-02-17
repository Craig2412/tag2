<?php

namespace Database\Seeders;

use App\Models\Origen;
use Illuminate\Database\Seeder;

class OrigenesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'facebook',
            'instagram',
            'whatsapp',
            'tiktok',
            'x',
        ];

        foreach ($items as $red) {
            Origen::firstOrCreate(['red' => $red]);
        }
    }
}
