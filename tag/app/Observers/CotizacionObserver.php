<?php

namespace App\Observers;

use App\Events\CotizacionGuardado;
use App\Models\Atencion;
use App\Models\Comision;
use App\Models\Cotizacion;
use App\Models\OrdenCompra;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;

class CotizacionObserver
{
    /**
     * Handle the Cotizacion "saved" event.
     */
    public function saved(Cotizacion $cotizacion): void
    {
        event(new CotizacionGuardado($cotizacion));
    }

    /**
     * Handle the Cotizacion "deleted" event.
     *
     * Aplica soft-delete en cascada a servicios y orden de compra.
     * Solo notifica a los listeners si NO es un borrado en cascada
     * (es decir, si el padre Atencion no está en trash).
     */
    public function deleted(Cotizacion $cotizacion): void
    {
        // Cascada: anular la comisión y su CxP. El endpoint de eliminación ya
        // aplica el bloqueo por pagos; aquí solo cubrimos borrados en cascada.
        $this->anularComisionEnCascada($cotizacion);

        // Notificar solo si no es borrado en cascada desde Atencion
        $atencion = Atencion::withTrashed()->find($cotizacion->id_atencion);
        if (!$atencion || !$atencion->trashed()) {
            event(new CotizacionGuardado($cotizacion));
        }

        // Cascada a servicios
        $cotizacion->servicios->each->delete();

        // Cascada a orden de compra (dispara OrdenCompraObserver::deleted)
        if ($ordenCompra = OrdenCompra::withTrashed()->where('id_cotizacion', $cotizacion->id)->first()) {
            if (!$ordenCompra->trashed()) {
                $ordenCompra->delete();
            }
        }
    }

    /**
     * Anula la comisión activa sin bloquear el borrado en cascada.
     */
    private function anularComisionEnCascada(Cotizacion $cotizacion): void
    {
        $comision = Comision::where('id_cotizacion', $cotizacion->id)
            ->where('estado', '!=', Comision::ESTADO_ANULADA)
            ->latest('id')
            ->first();

        if (! $comision) {
            return;
        }

        $comision->cuentaPorPagar?->delete();

        $comision->update([
            'estado' => Comision::ESTADO_ANULADA,
            'anulada_at' => now(),
            'anulada_por' => Auth::id(),
            'motivo_anulacion' => 'Cotización eliminada en cascada',
        ]);
    }
}
