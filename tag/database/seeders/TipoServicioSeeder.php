<?php

namespace Database\Seeders;

use App\Models\TipoServicio;
use Illuminate\Database\Seeder;

class TipoServicioSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'Alojamiento',
            'Traslado',
            'Vuelo',
            'Alimentación',
            'Consultoría',
        ];

        foreach ($tipos as $tipo) {
            TipoServicio::firstOrCreate([
                'tipo_servicio' => $tipo,
                'iva_defecto' => 16.00,
            ]);
        }
    }
}
