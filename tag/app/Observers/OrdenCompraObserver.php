<?php

namespace App\Observers;

use App\Models\OrdenCompra;
use App\Models\Cotizacion;
use App\Models\Atencion;
use App\Services\EstadoFaseService;

class OrdenCompraObserver
{
    /**
     * Handle the OrdenCompra "saved" event.
     */
    public function saved(OrdenCompra $ordenCompra): void
    {
        $this->sincronizarPadre($ordenCompra);
        // Además, al guardarse por primera vez o actualizarse precios, hay que sincronizar su propio estado financiero.
        EstadoFaseService::sincronizarEstadoFinanciero($ordenCompra);
    }

    /**
     * Handle the OrdenCompra "deleted" event.
     */
    public function deleted(OrdenCompra $ordenCompra): void
    {
        $this->sincronizarPadre($ordenCompra);
    }

    private function sincronizarPadre(OrdenCompra $ordenCompra): void
    {
        if ($cotizacion = Cotizacion::find($ordenCompra->id_cotizacion)) { // Ya no es nullable
            if ($atencion = Atencion::find($cotizacion->id_atencion)) {
                EstadoFaseService::sincronizarFaseAtencion($atencion);
            }
        }
    }
}
