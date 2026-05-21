<?php

namespace App\Observers;

use App\Events\AtencionBroadcast;
use App\Models\Atencion;

class AtencionObserver
{
    /**
     * Al crear una Atención, notificar en tiempo real al frontend.
     */
    public function created(Atencion $atencion): void
    {
        broadcast(new AtencionBroadcast($atencion, 'created'));
    }

    /**
     * Al actualizar una Atención, notificar en tiempo real al frontend.
     */
    public function updated(Atencion $atencion): void
    {
        broadcast(new AtencionBroadcast($atencion, 'updated'));
    }

    /**
     * Al eliminar una Atención:
     * 1. Se aplica soft-delete en cascada a todas sus cotizaciones.
     * 2. Se notifica al frontend en tiempo real (payload mínimo: solo id).
     *
     * El resto de la cadena (servicios, orden de compra, cuentas por pagar)
     * se resuelve en CotizacionObserver y OrdenCompraObserver.
     */
    public function deleted(Atencion $atencion): void
    {
        $atencion->cotizaciones->each->delete();

        broadcast(new AtencionBroadcast($atencion, 'deleted'));
    }
}
