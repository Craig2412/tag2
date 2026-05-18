<?php

namespace App\Observers;

use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Models\OrdenCompra;
use App\Services\EstadoFaseService;

/**
 * Protege la integridad de los estados financieros cuando se elimina
 * un Pago de cliente (soft-delete o force-delete).
 */
class PagoObserver
{
    /**
     * Antes de eliminar el pago, recolectar las OC afectadas,
     * borrar los pivotes y recalcular el estado financiero de cada una.
     */
    public function deleting(Pago $pago): void
    {
        // Recolectar las OC afectadas antes de borrar los pivotes
        $ordenesIds = PagoOrdenCompra::where('id_pago', $pago->id)
            ->pluck('id_orden_compra')
            ->unique();

        // Borrar los pivotes
        PagoOrdenCompra::where('id_pago', $pago->id)->delete();

        // Sincronizar cada OC afectada
        foreach ($ordenesIds as $ordenId) {
            if ($orden = OrdenCompra::find($ordenId)) {
                EstadoFaseService::sincronizarEstadoFinanciero($orden);
            }
        }
    }
}
