<?php

namespace Database\Seeders;

use App\Models\OrdenCompraHistorial;
use Illuminate\Database\Seeder;

class OrdenCompraHistorialSeeder extends Seeder
{
    public function run(): void
    {
        OrdenCompraHistorial::factory()->count(5)->create();
    }
}
