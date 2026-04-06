<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\TipoServicio;
use Illuminate\Database\Seeder;

class TipoServicioSeeder extends Seeder
{
    public function run(): void
    {
        $proveedor = Proveedor::first();

        if (!$proveedor) {
            return;
        }

        TipoServicio::firstOrCreate(
            [
                'tipo_servicio' => 'consultoria',
                'id_proveedor' => $proveedor->id,
            ]
        );
    }
}
