<?php

namespace Database\Seeders;

use App\Models\CuentaProveedor;
use App\Models\Proveedor;
use App\Models\TipoContribuyente;
use Illuminate\Database\Seeder;

class CuentasProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $proveedor = Proveedor::first();
        $tipo = TipoContribuyente::first();

        if (!$proveedor || !$tipo) {
            return;
        }

        CuentaProveedor::firstOrCreate(
            [
                'id_proveedor' => $proveedor->id,
                'numero_cuenta' => '0102-0000-0000-0000-0000',
            ],
            [
                'entidad_financiera' => 'Banco Demo',
                'tipo_cuenta' => 'corriente',
                'moneda' => 'VES',
                'id_tipo_contribuyente' => $tipo->id,
            ]
        );
    }
}
