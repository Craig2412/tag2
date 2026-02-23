<?php

namespace Database\Seeders;

use App\Models\TipoContribuyente;
use Illuminate\Database\Seeder;

class TiposContribuyentesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['tipo_contribuyente' => 'Normal', 'porcentaje_iva' => 16],
            ['tipo_contribuyente' => 'Reducido', 'porcentaje_iva' => 8],
            ['tipo_contribuyente' => 'Exento', 'porcentaje_iva' => 0],
        ];

        foreach ($items as $item) {
            TipoContribuyente::firstOrCreate(
                ['tipo_contribuyente' => $item['tipo_contribuyente']],
                ['porcentaje_iva' => $item['porcentaje_iva']]
            );
        }
    }
}
