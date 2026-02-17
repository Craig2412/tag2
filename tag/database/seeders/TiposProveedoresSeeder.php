<?php

namespace Database\Seeders;

use App\Models\TipoProveedor;
use Illuminate\Database\Seeder;

class TiposProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'servicios profesionales',
            'logistica',
            'tecnologia',
        ];

        foreach ($items as $tipo) {
            TipoProveedor::firstOrCreate(['tipo_proveedor' => $tipo]);
        }
    }
}
