<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EntidadBancaria;

class EntidadBancariaSeeder extends Seeder
{
    public function run(): void
    {
        EntidadBancaria::create([
            'entidad' => 'Banco de Prueba',
        ]);
        EntidadBancaria::create([
            'entidad' => 'Banco Ejemplo',
        ]);
        EntidadBancaria::create([
            'entidad' => 'Banco Nacional',
        ]);
        EntidadBancaria::create([
            'entidad' => 'Banco Internacional',
        ]);
    }
}
