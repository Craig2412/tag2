<?php

namespace App\Observers;

use App\Models\OrdenCompra;
use App\Models\Cotizacion;
use App\Models\Atencion;
use App\Models\EstadoCotizacion;
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
     * Cuando se elimina una OC, la cotización que la originó ya no es válida
     * (se "quemó"). La marcamos como rechazada automáticamente.
     * El usuario deberá crear una nueva cotización desde cero.
     */
    public function deleted(OrdenCompra $ordenCompra): void
    {
        // Marcar la cotización origen como rechazada — ya no sirve
        if ($cotizacion = Cotizacion::find($ordenCompra->id_cotizacion)) {
            $idRechazada = EstadoCotizacion::where('slug', 'rechazada')->value('id');
            if ($idRechazada && (int) $cotizacion->id_estado_cotizacion !== $idRechazada) {
                $cotizacion->updateQuietly(['id_estado_cotizacion' => $idRechazada]);
            }
        }

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
