<?php

namespace App\Listeners;

use App\Events\OrdenCompraGuardado;
use App\Models\Cotizacion;
use App\Models\Atencion;
use App\Services\EstadoFaseService;

class SincronizarPadreOrdenCompraListener
{
    public function handle(OrdenCompraGuardado $event): void
    {
        $orden = $event->orden;
        if ($cot = Cotizacion::find($orden->id_cotizacion)) {
            if ($at = Atencion::find($cot->id_atencion)) {
                EstadoFaseService::sincronizarFaseAtencion($at);
            }
        }
    }
}
