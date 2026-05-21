<?php

namespace App\Listeners;

use App\Events\OrdenCompraAprobada;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerarCuentasPorPagarListener implements ShouldQueue
{
    public function handle(OrdenCompraAprobada $event): void
    {
        $orden = $event->orden;

        // Delegamos toda la lógica al modelo para mantener DRY
        $orden->sincronizarCuentasPorPagar();
    }
}
