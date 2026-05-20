<?php

namespace App\Observers;

use App\Events\AtencionEstatusActualizado;
use App\Events\AtencionEtapaCambiada;
use App\Events\OrdenCompraGuardado;
use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\EstadoCotizacion;
use App\Models\OrdenCompra;
use App\Services\AtencionStateService;

class OrdenCompraObserver
{
    /**
     * Emite el evento genérico de guardado para que los listeners
     * (SincronizarPadre, SincronizarEstadoFinanciero) reaccionen.
     */
    public function saved(OrdenCompra $ordenCompra): void
    {
        event(new OrdenCompraGuardado($ordenCompra));
    }

    /**
     * Cuando se elimina una OC, la cotización que la originó ya no es válida.
     */
    public function deleted(OrdenCompra $ordenCompra): void
    {
        // Marcar la cotización origen como rechazada
        if ($cotizacion = Cotizacion::find($ordenCompra->id_cotizacion)) {
            $idRechazada = EstadoCotizacion::where('slug', 'rechazada')->value('id');
            if ($idRechazada && (int) $cotizacion->id_estado_cotizacion !== $idRechazada) {
                $cotizacion->update(['id_estado_cotizacion' => $idRechazada]);
            }
        }

        $this->sincronizarPadre($ordenCompra);
    }

    private function sincronizarPadre(OrdenCompra $ordenCompra): void
    {
        if ($cotizacion = Cotizacion::find($ordenCompra->id_cotizacion)) {
            if ($atencion = Atencion::find($cotizacion->id_atencion)) {
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
}
