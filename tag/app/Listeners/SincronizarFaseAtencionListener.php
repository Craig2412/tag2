<?php

namespace App\Listeners;

use App\Events\CotizacionGuardado;
use App\Models\Atencion;
use App\Services\EstadoFaseService;

class SincronizarFaseAtencionListener
{
    public function handle(CotizacionGuardado $event): void
    {
        if ($atencion = Atencion::find($event->cotizacion->id_atencion)) {
            EstadoFaseService::sincronizarFaseAtencion($atencion);
        }
    }
}
