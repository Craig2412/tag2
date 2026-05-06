<?php

namespace App\Listeners;

use App\Events\CotizacionEstatusActualizado;
use App\Models\CotizacionHistorial;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegistrarHistorialEstatusCotizacionListener implements ShouldQueue
{
    public function handle(CotizacionEstatusActualizado $event): void
    {
        CotizacionHistorial::create([
            'cotizacion_id'   => $event->cotizacion->id,
            'estatus_anterior' => $event->estatusAnterior,
            'estatus_nuevo'   => $event->estatusNuevo,
            'usuario_id'      => $event->usuarioId,
            'comentario'      => $event->comentario,
        ]);
    }

    public function __invoke(CotizacionEstatusActualizado $event): void
    {
        $this->handle($event);
    }
}
