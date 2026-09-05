<?php

namespace App\Listeners;

use App\Events\OrdenCompraGuardado;
use App\Events\PagoOrdenCompraGuardado;
use App\Models\OrdenCompra;
use App\Services\OrdenStateService;

/**
 * Sincroniza el estado financiero de ingreso de una Orden de Compra.
 *
 * Se ejecuta de forma SÍNCRONA: los estados financieros deben reflejarse
 * inmediatamente en la interfaz al registrar un pago de cliente. Usar cola
 * aquí dejaba la OC sin actualizar si no había worker activo.
 */
class SincronizarEstadoFinancieroListener
{
    /**
     * Guard de re-entrada: evita que los update() internos de OrdenStateService
     * disparen este listener de forma anidada y generen un bucle.
     */
    private static bool $procesando = false;

    /**
     * Handle the event.
     *
     * @param  mixed  $event
     */
    public function handle($event): void
    {
        if (self::$procesando) {
            return;
        }

        self::$procesando = true;

        try {
            if ($event instanceof OrdenCompraGuardado) {
                OrdenStateService::sincronizarFinanciero($event->orden);
            } elseif ($event instanceof PagoOrdenCompraGuardado) {
                if ($orden = OrdenCompra::find($event->pagoOrden->id_orden_compra)) {
                    OrdenStateService::sincronizarFinanciero($orden);
                }
            }
        } finally {
            self::$procesando = false;
        }
    }
}
