<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompraHistorial;

class OrdenCompraHistorialSeeder extends Seeder
{
    public function run(): void
    {
        OrdenCompraHistorial::factory()->count(5)->create();
    }
}
