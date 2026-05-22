<?php

namespace Database\Seeders;

use App\Models\AtencionHistorial;
use Illuminate\Database\Seeder;

class AtencionHistorialSeeder extends Seeder
{
    public function run(): void
    {
        AtencionHistorial::factory()->count(5)->create();
    }
}
