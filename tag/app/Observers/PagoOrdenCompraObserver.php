<?php

namespace App\Observers;

use App\Models\PagoOrdenCompra;
use App\Models\OrdenCompra;
use App\Services\EstadoFaseService;

class PagoOrdenCompraObserver
{
    /**
     * Handle the PagoOrdenCompra "saved" event.
     * Triggers when a new payment link is created or updated.
     */
    public function saved(PagoOrdenCompra $pagoOrden): void
    {
        if ($orden = OrdenCompra::find($pagoOrden->id_orden_compra)) {
            EstadoFaseService::sincronizarEstadoFinanciero($orden);
        }
    }

    /**
     * Handle the PagoOrdenCompra "deleted" event.
     */
    public function deleted(PagoOrdenCompra $pagoOrden): void
    {
        if ($orden = OrdenCompra::find($pagoOrden->id_orden_compra)) {
            EstadoFaseService::sincronizarEstadoFinanciero($orden);
        }
    }
}
