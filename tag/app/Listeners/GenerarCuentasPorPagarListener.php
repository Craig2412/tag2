<?php

namespace App\Listeners;

use App\Events\OrdenCompraAprobada;
use App\Models\CuentaPorPagar;
use App\Models\Servicio;
use App\Models\EstadoFinanciero;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerarCuentasPorPagarListener implements ShouldQueue
{
    public function handle(OrdenCompraAprobada $event): void
    {
        $orden = $event->orden;
        // Los servicios pertenecen a la Cotizacion, no directamente a la OrdenCompra
        $servicios = Servicio::where('id_cotizacion', $orden->id_cotizacion)->get();

        // Agrupar por proveedor
        $porProveedor = $servicios->groupBy('id_proveedor');

        foreach ($porProveedor as $idProveedor => $servs) {
            $monto = $servs->sum('costo'); // solo el campo costo, sin IVA
            
            // Buscamos el estado financiero "pendiente" de forma dinámica
            $estadoPendiente = EstadoFinanciero::where('slug', 'pendiente')->first();
            $idEstado = $estadoPendiente ? $estadoPendiente->id : 1;

            // Solo creamos si no existe ya para esta orden y proveedor
            CuentaPorPagar::firstOrCreate(
                [
                    'id_orden_compra' => $orden->id,
                    'id_proveedor'    => $idProveedor,
                ],
                [
                    'monto_total'     => $monto,
                    'saldo_pendiente' => $monto,
                    'id_estado_financiero' => $idEstado,
                ]
            );
        }

        // Sincronizar el estado de egreso inicial de la orden
        \App\Services\EstadoFaseService::sincronizarEstadoEgreso($orden);
    }
}
