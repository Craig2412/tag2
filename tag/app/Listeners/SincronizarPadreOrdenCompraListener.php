<?php

namespace App\Listeners;

use App\Events\AtencionEstatusActualizado;
use App\Events\AtencionEtapaCambiada;
use App\Events\OrdenCompraGuardado;
use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Services\AtencionStateService;
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
                $result = AtencionStateService::sincronizarFase($at);

                // Disparar eventos basados en el DTO retornado por el servicio
                if ($result->etapa->huboCambio) {
                    event(new AtencionEtapaCambiada($at, $result->etapa->anterior, $result->etapa->nuevo));
                }
                if ($result->estatus->huboCambio) {
                    event(new AtencionEstatusActualizado($at, $result->estatus->anterior, $result->estatus->nuevo, $result->estatus->comentario));
                }
            }
        }
    }
}
