<?php

namespace Database\Seeders;

use App\Models\CuentaProveedor;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class CuentasProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $proveedor = Proveedor::first();

        if (! $proveedor) {
            return;
        }

        CuentaProveedor::firstOrCreate(
            [
                'id_proveedor' => $proveedor->id,
                'numero_cuenta' => '0102-0000-0000-0000-0000',
            ],
            [
                'nombre_banco' => 'Banco Demo',
                'tipo_cuenta' => 'corriente',
                'moneda' => 'VES',
            ]
        );
    }
}
