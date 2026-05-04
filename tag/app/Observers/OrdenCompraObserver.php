<?php

namespace App\Observers;

use App\Models\OrdenCompra;
use App\Models\Cotizacion;
use App\Models\Atencion;
use App\Services\EstadoFaseService;
use App\Services\EstatusResolver;

class OrdenCompraObserver
{
    /**
     * Handle the OrdenCompra "saved" event.
     */
    public function saved(OrdenCompra $ordenCompra): void
    {
        // Emitimos evento genérico de guardado
        event(new \App\Events\OrdenCompraGuardado($ordenCompra));

        // EstatusResolver usa cache de 5min — evita query a DB en cada save()
        $estatusAprobada = EstatusResolver::id('aprobada');

        if ($estatusAprobada === null) {
            return;
        }

        $esAprobada = (int) $ordenCompra->getRawOriginal('estatus') === (int) $estatusAprobada;

        if ($esAprobada && ($ordenCompra->wasChanged('estatus') || $ordenCompra->wasRecentlyCreated)) {
            event(new \App\Events\OrdenCompraAprobada($ordenCompra));
        }
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
        if ($cotizacion = Cotizacion::find($ordenCompra->id_cotizacion)) {
            if ($atencion = Atencion::find($cotizacion->id_atencion)) {
                EstadoFaseService::sincronizarFaseAtencion($atencion);
            }
        }
    }
}
