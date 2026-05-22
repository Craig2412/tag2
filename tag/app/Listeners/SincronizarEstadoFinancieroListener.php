<?php

namespace App\Listeners;

use App\Events\OrdenCompraGuardado;
use App\Events\PagoOrdenCompraGuardado;
use App\Models\OrdenCompra;
use App\Services\OrdenStateService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SincronizarEstadoFinancieroListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     */
    public function handle($event): void
    {
        if ($event instanceof OrdenCompraGuardado) {
            OrdenStateService::sincronizarFinanciero($event->orden);
        } elseif ($event instanceof PagoOrdenCompraGuardado) {
            if ($orden = OrdenCompra::find($event->pagoOrden->id_orden_compra)) {
                OrdenStateService::sincronizarFinanciero($orden);
            }
        }
    }
}
