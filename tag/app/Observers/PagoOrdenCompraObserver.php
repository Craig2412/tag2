<?php

namespace App\Observers;

use App\Events\PagoOrdenCompraGuardado;
use App\Models\PagoOrdenCompra;

class PagoOrdenCompraObserver
{
    /**
     * Dispara el evento PagoOrdenCompraGuardado.
     * SincronizarEstadoFinancieroListener se encarga de llamar al servicio.
     */
    public function saved(PagoOrdenCompra $pagoOrden): void
    {
        event(new PagoOrdenCompraGuardado($pagoOrden));
    }

    /**
     * Dispara el evento PagoOrdenCompraGuardado al eliminar.
     */
    public function deleted(PagoOrdenCompra $pagoOrden): void
    {
        event(new PagoOrdenCompraGuardado($pagoOrden));
    }
}
