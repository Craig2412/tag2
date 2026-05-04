<?php

namespace App\Listeners;

use App\Events\AtencionEtapaCambiada;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RegistrarHistorialAtencionListener
{
    public function handle(AtencionEtapaCambiada $event): void
    {
        DB::table('atencion_historial')->insert([
            'atencion_id' => $event->atencion->id,
            'estatus_anterior' => $event->etapaAnterior,
            'estatus_nuevo' => $event->etapaNueva,
            'usuario_id' => Auth::id() ?? 1,
            'comentario' => 'Cambio de etapa automático por eventos/sistema.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
