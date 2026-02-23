<?php

namespace Database\Seeders;

use App\Models\ConfiguracionSistema;
use Illuminate\Database\Seeder;

class ConfiguracionesSistemaSeeder extends Seeder
{
    public function run(): void
    {
        ConfiguracionSistema::firstOrCreate(
            ['id' => 1],
            ['dias_vencimiento' => 30]
        );
    }
}
