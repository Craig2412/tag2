<?php

namespace App\Observers;

use App\Events\CotizacionGuardado;
use App\Models\Cotizacion;

class CotizacionObserver
{
    /**
     * Handle the Cotizacion "saved" event.
     */
    public function saved(Cotizacion $cotizacion): void
    {
        event(new CotizacionGuardado($cotizacion));
    }

    /**
     * Handle the Cotizacion "deleted" event.
     */
    public function deleted(Cotizacion $cotizacion): void
    {
        event(new CotizacionGuardado($cotizacion));
    }
}
