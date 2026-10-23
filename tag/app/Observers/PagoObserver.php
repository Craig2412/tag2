<?php

namespace App\Observers;

use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Services\EstadoFaseService;
use App\Services\OrdenStateService;

/**
 * Protege la integridad de los estados financieros cuando se modifica o elimina
 * un Pago de cliente (soft-delete o force-delete).
 *
 * El estado de conciliación determina cuánto cuenta un pago para el ingreso de
 * la orden, por lo que un cambio en él también exige recalcular las OC afectadas.
 */
class PagoObserver
{
    /**
     * Al cambiar el estado de conciliación, recalcular el estado financiero
     * de cada OC afectada: solo los pagos "conciliado" suman al ingreso.
     */
    public function updated(Pago $pago): void
    {
        if (! $pago->wasChanged('id_estado_conciliacion')) {
            return;
        }

        $this->sincronizarOrdenesAfectadas($pago);
    }

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

    /**
     * Recalcula el estado financiero de las OC a las que está asignado el pago.
     */
    private function sincronizarOrdenesAfectadas(Pago $pago): void
    {
        $ordenesIds = PagoOrdenCompra::where('id_pago', $pago->id)
            ->pluck('id_orden_compra')
            ->unique();

        foreach ($ordenesIds as $ordenId) {
            if ($orden = OrdenCompra::find($ordenId)) {
                OrdenStateService::sincronizarFinanciero($orden);
            }
        }
    }
}
