<?php

namespace App\Listeners;

use App\Events\OrdenCompraGuardado;
use App\Events\PagoOrdenCompraGuardado;
use App\Models\OrdenCompra;
use App\Services\EstadoFaseService;

class SincronizarEstadoFinancieroListener
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event): void
    {
        if ($event instanceof OrdenCompraGuardado) {
            EstadoFaseService::sincronizarEstadoFinanciero($event->orden);
        } elseif ($event instanceof PagoOrdenCompraGuardado) {
            if ($orden = OrdenCompra::find($event->pagoOrden->id_orden_compra)) {
                EstadoFaseService::sincronizarEstadoFinanciero($orden);
            }
        }
    }
}
