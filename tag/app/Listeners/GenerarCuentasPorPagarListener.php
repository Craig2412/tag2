<?php

namespace App\Listeners;

use App\Events\OrdenCompraAprobada;
use App\Models\CuentaPorPagar;
use App\Models\Servicio;

class GenerarCuentasPorPagarListener
{
    public function handle(OrdenCompraAprobada $event): void
    {
        $orden = $event->orden;
        $servicios = Servicio::where('id_orden_compra', $orden->id)->get();

        // Agrupar por proveedor
        $porProveedor = $servicios->groupBy('id_proveedor');

        foreach ($porProveedor as $idProveedor => $servs) {
            $monto = $servs->sum('costo'); // solo el campo costo, sin IVA
            
            // Buscamos el estatus "por pagar" o "pendiente" de forma dinámica
            $estatusPorPagar = \App\Models\Estatus::where('estatus', 'like', '%por pagar%')->first();
            $idEstatus = $estatusPorPagar ? $estatusPorPagar->id : 1;

            // Solo creamos si no existe ya para esta orden y proveedor
            CuentaPorPagar::firstOrCreate(
                [
                    'id_orden_compra' => $orden->id,
                    'id_proveedor'    => $idProveedor,
                ],
                [
                    'monto_total'     => $monto,
                    'saldo_pendiente' => $monto,
                    'estatus'         => $idEstatus,
                ]
            );
        }

        // Sincronizar el estado de egreso inicial de la orden
        \App\Services\EstadoFaseService::sincronizarEstadoEgreso($orden);
    }
}
