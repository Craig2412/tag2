<?php

namespace App\Listeners;

use App\Events\AtencionEstatusActualizado;
use App\Models\AtencionHistorial;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegistrarHistorialEstatusAtencionListener implements ShouldQueue
{
    public function handle(AtencionEstatusActualizado $event): void
    {
        AtencionHistorial::create([
            'atencion_id'     => $event->atencion->id,
            'estatus_anterior' => $event->estatusAnterior,
            'estatus_nuevo'   => $event->estatusNuevo,
            'usuario_id'      => $event->usuarioId,
            'comentario'      => $event->comentario,
        ]);
    }

    public function __invoke(AtencionEstatusActualizado $event): void
    {
        $this->handle($event);
    }
}
