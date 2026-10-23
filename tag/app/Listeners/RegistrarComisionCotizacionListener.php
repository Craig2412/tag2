<?php

namespace App\Listeners;

use App\Events\CotizacionEstatusActualizado;
use App\Models\EstadoCotizacion;
use App\Services\ComisionService;

/**
 * Materializes (and reverses) the commission agreed on a quote.
 *
 * This listener is intentionally synchronous: it must run inside the same DB
 * transaction as the quote status change so the commission, its payable and the
 * purchase order are created/reversed atomically.
 */
class RegistrarComisionCotizacionListener
{
    public function __construct(private readonly ComisionService $comisiones)
    {
    }

    public function handle(CotizacionEstatusActualizado $event): void
    {
        $idAprobada = EstadoCotizacion::where('slug', 'aprobada')->value('id');
        $idRechazada = EstadoCotizacion::where('slug', 'rechazada')->value('id');

        if ($idAprobada && (int) $event->estatusNuevo === (int) $idAprobada) {
            if (! empty($event->comision)) {
                $this->comisiones->registrarDesdeCotizacion($event->cotizacion, $event->comision);
            }

            return;
        }

        if ($idRechazada && (int) $event->estatusNuevo === (int) $idRechazada) {
            $this->comisiones->anularPorCotizacion($event->cotizacion, 'Cotización rechazada');
        }
    }
}
