<?php

namespace App\Observers;

use App\Models\Servicio;

class ServicioObserver
{
    /**
     * Handle the Servicio "saving" event.
     * Before creating or updating, compute totals to enforce business rules.
     */
    public function saving(Servicio $servicio): void
    {
        $gravable = (float) $servicio->monto_gravable;
        $noSujeto = (float) $servicio->monto_no_sujeto;
        $ivaPorcentaje = (float) $servicio->iva_establecido;
        
        $montoIva = $gravable * ($ivaPorcentaje / 100);

        // El Total real es siempre base_imponible + impuestos
        $servicio->total_servicio = $gravable + $montoIva + $noSujeto;
    }
}
