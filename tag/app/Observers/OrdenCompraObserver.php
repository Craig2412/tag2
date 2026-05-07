<?php

namespace App\Observers;

use App\Models\OrdenCompra;
use App\Models\Cotizacion;
use App\Models\Atencion;
use App\Services\EstadoFaseService;
use App\Events\OrdenCompraGuardado;

class OrdenCompraObserver
{
    /**
     * Emite el evento genérico de guardado para que los listeners
     * (SincronizarPadre, SincronizarEstadoFinanciero) reaccionen.
     * La lógica de "¿está aprobada?" ya no vive aquí:
     * GenerarOrdenDesdeCotizacionListener dispara OrdenCompraAprobada directamente.
     */
    public function saved(OrdenCompra $ordenCompra): void
    {
        event(new OrdenCompraGuardado($ordenCompra));
    }

    /**
     * Cuando se elimina una OC, sincronizamos el padre para que la Atención
     * retroceda de fase si corresponde.
     */
    public function deleted(OrdenCompra $ordenCompra): void
    {
        $this->sincronizarPadre($ordenCompra);
    }

    private function sincronizarPadre(OrdenCompra $ordenCompra): void
    {
        if ($cotizacion = Cotizacion::find($ordenCompra->id_cotizacion)) {
            if ($atencion = Atencion::find($cotizacion->id_atencion)) {
                EstadoFaseService::sincronizarFaseAtencion($atencion);
            }
        }
    }
}
