<?php

namespace App\Listeners;

use App\Events\CotizacionEstatusActualizado;
use App\Models\CotizacionHistorial;

class RegistrarHistorialEstatusCotizacionListener
{
    public function handle(CotizacionEstatusActualizado $event): void
    {
        CotizacionHistorial::create([
            'cotizacion_id'   => $event->cotizacion->id,
            'estatus_anterior' => $event->estatusAnterior,
            'estatus_nuevo'   => $event->estatusNuevo,
            'usuario_id'      => auth()->id(),
            'comentario'      => $event->comentario,
        ]);
    }
}
