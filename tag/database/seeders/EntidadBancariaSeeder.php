<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EntidadBancaria;
use App\Models\Estatus;

class EntidadBancariaSeeder extends Seeder
{
    public function run(): void
    {
        $estatusActivo = Estatus::where('estatus', 'activo')->first();
        if ($estatusActivo) {
            EntidadBancaria::create([
                'entidad' => 'Banco de Prueba',
                'estatus' => $estatusActivo->id,
            ]);
            EntidadBancaria::create([
                'entidad' => 'Banco Ejemplo',
                'estatus' => $estatusActivo->id,
            ]);
            EntidadBancaria::create([
                'entidad' => 'Banco Nacional',
                'estatus' => $estatusActivo->id,
            ]);
            EntidadBancaria::create([
                'entidad' => 'Banco Internacional',
                'estatus' => $estatusActivo->id,
            ]);
        }
    }
}
