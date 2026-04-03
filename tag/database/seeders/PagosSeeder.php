<?php

namespace Database\Seeders;

use App\Models\Estatus;
use App\Models\MetodoPago;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Models\TasaCambio;
use Illuminate\Database\Seeder;

class PagosSeeder extends Seeder
{
    public function run(): void
    {
        $ordenesCompra = OrdenCompra::query()
            ->orderBy('id')
            ->get();
        $metodo = MetodoPago::first();
        $tasa = TasaCambio::first();

        if ($ordenesCompra->count() < 1 || !$metodo || !$tasa) {
            return;
        }

        $detalles = [];

        foreach ($ordenesCompra as $ordenCompra) {
            $ordenCompra->recalcularMontoTotal();
            $totalServicios = (float) $ordenCompra->monto_total;

            if ($totalServicios <= 0) {
                continue;
            }

            $detalles[] = [
                'id_orden_compra' => $ordenCompra->id,
                'monto_asignado' => round($totalServicios * 0.4, 2),
            ];
        }

        if (count($detalles) === 0) {
            return;
        }

        $montoTotal = array_sum(array_column($detalles, 'monto_asignado'));

        $estatus = Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);

        $pago = Pago::firstOrCreate(
            [
                'nro_comprobante' => 'COMP-0001',
            ],
            [
                'fecha_pago' => now()->toDateString(),
                'monto_total' => $montoTotal,
                'id_metodo_pago' => $metodo->id,
                'id_tasa_cambio' => $tasa->id,
                'estatus' => $estatus->id,
                'borrado_logico' => false,
            ]
        );

        foreach ($detalles as $detalle) {
            PagoOrdenCompra::firstOrCreate(
                [
                    'id_pago' => $pago->id,
                    'id_orden_compra' => $detalle['id_orden_compra'],
                ],
                [
                    'monto_asignado' => $detalle['monto_asignado'],
                ]
            );
        }
    }
}
