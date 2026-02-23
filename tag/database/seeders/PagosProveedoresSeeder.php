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
        $servicio = Servicio::first();
        $metodoPago = MetodoPago::first();

        if (!$servicio || !$metodoPago) {
            return;
        }

        PagoProveedor::firstOrCreate(
            [
                'id_servicio' => $servicio->id,
                'referencia' => 'PP-0001',
            ],
            [
                'monto' => $servicio->total_servicio,
                'fecha_pago' => now()->toDateString(),
                'id_metodo_pago' => $metodoPago->id,
            ]
        );

        $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);
        $servicio->update(['estatus' => $estatusPagado->id]);
    }
}
