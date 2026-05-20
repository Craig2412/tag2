<?php

namespace App\Listeners;

use App\Events\AtencionEstatusActualizado;
use App\Events\AtencionEtapaCambiada;
use App\Events\CotizacionGuardado;
use App\Models\Atencion;
use App\Services\AtencionStateService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SincronizarFaseAtencionListener implements ShouldQueue
{
    public function handle(CotizacionGuardado $event): void
    {
        if ($atencion = Atencion::find($event->cotizacion->id_atencion)) {
            $result = AtencionStateService::sincronizarFase($atencion);

            // Disparar eventos basados en el DTO retornado por el servicio
            if ($result->etapa->huboCambio) {
                event(new AtencionEtapaCambiada($atencion, $result->etapa->anterior, $result->etapa->nuevo));
            }
            if ($result->estatus->huboCambio) {
                event(new AtencionEstatusActualizado($atencion, $result->estatus->anterior, $result->estatus->nuevo, $result->estatus->comentario));
            }
        }
    }
}
