<?php

namespace App\Listeners;

use App\Events\AtencionEstatusActualizado;
use App\Models\AtencionHistorial;

class RegistrarHistorialEstatusAtencionListener
{
    public function handle(AtencionEstatusActualizado $event): void
    {
        AtencionHistorial::create([
            'atencion_id'     => $event->atencion->id,
            'estatus_anterior' => $event->estatusAnterior,
            'estatus_nuevo'   => $event->estatusNuevo,
            'usuario_id'      => auth()->id(),
            'comentario'      => $event->comentario,
        ]);
    }
}
