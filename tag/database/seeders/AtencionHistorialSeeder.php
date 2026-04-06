<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AtencionHistorial;

class AtencionHistorialSeeder extends Seeder
{
    public function run(): void
    {
        AtencionHistorial::factory()->count(5)->create();
    }
}
