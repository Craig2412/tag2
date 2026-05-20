<?php

namespace App\Listeners;

use App\Events\AtencionEtapaCambiada;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistrarHistorialAtencionListener
{
    public function handle(AtencionEtapaCambiada $event): void
    {
        DB::table('atencion_historial')->insert([
            'atencion_id' => $event->atencion->id,
            'id_etapa_anterior' => $event->etapaAnterior,
            'id_etapa_nueva' => $event->etapaNueva,
            'usuario_id' => Auth::id() ?? 1,
            'comentario' => 'Cambio de etapa automático por eventos/sistema.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
