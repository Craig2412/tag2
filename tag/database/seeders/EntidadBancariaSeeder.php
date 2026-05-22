<?php

namespace Database\Seeders;

use App\Models\EntidadBancaria;
use Illuminate\Database\Seeder;

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
