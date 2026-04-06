<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CotizacionHistorial;

class CotizacionHistorialSeeder extends Seeder
{
    public function run(): void
    {
        CotizacionHistorial::factory()->count(5)->create();
    }
}
