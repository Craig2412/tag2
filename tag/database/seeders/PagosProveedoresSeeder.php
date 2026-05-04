<?php

namespace Database\Seeders;

use App\Models\Estatus;
use App\Models\MetodoPago;
use App\Models\PagoProveedor;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class PagosProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $proveedor = \App\Models\Proveedor::first();
        $metodoPago = MetodoPago::first();

        if (!$proveedor || !$metodoPago) {
            return;
        }

        PagoProveedor::firstOrCreate(
            [
                'referencia' => 'PP-0001',
            ],
            [
                'id_proveedor' => $proveedor->id,
                'monto_total' => 1500.00, // Monto de ejemplo
                'fecha_pago' => now()->toDateString(),
                'id_metodo_pago' => $metodoPago->id,
            ]
        );
    }
}
