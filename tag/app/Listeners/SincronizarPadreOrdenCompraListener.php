<?php

namespace App\Listeners;

use App\Events\OrdenCompraGuardado;
use App\Models\Cotizacion;
use App\Models\Atencion;
use App\Services\EstadoFaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SincronizarPadreOrdenCompraListener implements ShouldQueue
{
    public function handle(OrdenCompraGuardado $event): void
    {
        $orden = $event->orden;
        Log::info("Ejecutando SincronizarPadreOrdenCompraListener para Orden #{$orden->id}");
        if ($cot = Cotizacion::find($orden->id_cotizacion)) {
            if ($at = Atencion::find($cot->id_atencion)) {
                EstadoFaseService::sincronizarFaseAtencion($at);
            }
        }
    }
}
