<?php

namespace App\Observers;

use App\Models\Cotizacion;
use App\Models\Atencion;
use App\Services\EstadoFaseService;
use App\Events\CotizacionGuardado;

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
