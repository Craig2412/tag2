<?php

namespace Database\Seeders;

use App\Models\CotizacionHistorial;
use Illuminate\Database\Seeder;

class CotizacionHistorialSeeder extends Seeder
{
    public function run(): void
    {
        CotizacionHistorial::factory()->count(5)->create();
    }
}
