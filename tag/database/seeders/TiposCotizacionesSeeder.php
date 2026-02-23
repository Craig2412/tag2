<?php

namespace Database\Seeders;

use App\Models\TipoCotizacion;
use Illuminate\Database\Seeder;

class TiposCotizacionesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'personal',
            'empresarial',
        ];

        foreach ($items as $tipo) {
            TipoCotizacion::firstOrCreate(['tipo_cotizacion' => $tipo]);
        }
    }
}
