<?php

namespace App\Listeners;

use App\Events\CotizacionGuardado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RegistrarHistorialCotizacionListener
{
    public function handle(CotizacionGuardado $event): void
    {
        $cotizacion = $event->cotizacion;

        // Verificar si el estatus cambió o si es nueva
        if ($cotizacion->wasChanged('estatus') || $cotizacion->wasRecentlyCreated) {
            
            $estatusAnterior = $cotizacion->wasRecentlyCreated ? null : $cotizacion->getOriginal('estatus');
            $estatusNuevo = $cotizacion->estatus;

            DB::table('cotizacion_historial')->insert([
                'cotizacion_id' => $cotizacion->id,
                'estatus_anterior' => $estatusAnterior,
                'estatus_nuevo' => $estatusNuevo,
                'usuario_id' => Auth::id() ?? 1,
                'comentario' => $cotizacion->wasRecentlyCreated ? 'Cotización creada.' : 'Actualización de estatus.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
