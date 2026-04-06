<?php

namespace App\Observers;

use App\Models\PagoOrdenCompra;
use App\Models\OrdenCompra;

class PagoOrdenCompraObserver
{
    /**
     * Handle the PagoOrdenCompra "saved" event.
     * Triggers when a new payment link is created or updated.
     */
    public function saved(PagoOrdenCompra $pagoOrden): void
    {
        $this->actualizarEstadoFinancieroOrden($pagoOrden->id_orden_compra);
    }

    /**
     * Handle the PagoOrdenCompra "deleted" event.
     */
    public function deleted(PagoOrdenCompra $pagoOrden): void
    {
        $this->actualizarEstadoFinancieroOrden($pagoOrden->id_orden_compra);
    }

    /**
     * Motor de Máquina de Estados:
     * Compara el monto total pagado en pivotes contra el monto facturado de la Orden.
     */
    private function actualizarEstadoFinancieroOrden(int $ordenId): void
    {
        $orden = OrdenCompra::find($ordenId);

        if (!$orden) {
            return;
        }

        // Sumar todos los pagos asignados a esta orden
        $totalPagado = PagoOrdenCompra::where('id_orden_compra', $ordenId)
            ->sum('monto_asignado');

        $totalFacturado = (float) $orden->monto_total;

        // Máquina de estados
        if ($totalPagado <= 0) {
            $nuevoEstado = 'POR_PAGAR';
        } elseif ($totalPagado >= $totalFacturado) {
            $nuevoEstado = 'PAGADO';
        } else {
            $nuevoEstado = 'PAGADO_PARCIALMENTE';
        }

        // Solo actualizar si hay un cambio real para no ensuciar los timestamps y evitar ciclos infinitos
        if ($orden->estado_financiero !== $nuevoEstado) {
            // Guardamos silenciosamente ignorando el timestamp normal si se prefiere,
            // pero standard save() es suficiente y registrará el cambio de estado.
            $orden->estado_financiero = $nuevoEstado;
            $orden->saveQuietly(); 
        }
    }
}
