<?php

namespace App\Listeners;

use App\Events\CotizacionEstatusActualizado;
use App\Events\OrdenCompraAprobada;
use App\Models\OrdenCompra;
use App\Models\Estatus;
use Illuminate\Support\Facades\Log;

class GenerarOrdenDesdeCotizacionListener
{
    /**
     * Maneja el evento de cambio de estatus de la cotización.
     */
    public function handle(CotizacionEstatusActualizado $event): void
    {
        $cotizacion = $event->cotizacion;
        $idNuevoEstatus = $event->estatusNuevo;

        // 1. Buscamos cuál es el ID de "Aprobada" en el catálogo
        $estatusAprobado = Estatus::where('estatus', 'like', '%aprob%')
            ->orWhere('estatus', 'like', '%acept%')
            ->first();

        // 2. Si el nuevo estatus coincide con "Aprobada", procedemos
        if ($estatusAprobado && $idNuevoEstatus == $estatusAprobado->id) {
            
            // Verificamos si ya existe una Orden de Compra para esta cotización (para no duplicar)
            $existe = OrdenCompra::where('id_cotizacion', $cotizacion->id)->exists();
            
            if (!$existe) {
                // 3. Calculamos el monto total sumando los servicios de la cotización
                $montoTotal = $cotizacion->servicios()->sum('total_servicio');

                // 4. Creamos la Orden de Compra
                $orden = OrdenCompra::create([
                    'id_cotizacion' => $cotizacion->id,
                    'monto_total'   => $montoTotal,
                    'estatus'       => 1, // En Proceso / Inicial
                    'id_estado_financiero' => 1, // Pendiente por cobrar
                    'id_estado_financiero_egreso' => 1, // Pendiente por pagar
                ]);

                Log::info("Orden de Compra #{$orden->id} generada automáticamente desde Cotización #{$cotizacion->id} por monto de $montoTotal");

                // 5. Disparamos el evento de Orden Aprobada para que se generen las Cuentas por Pagar
                event(new OrdenCompraAprobada($orden));
            }
        }
    }
}
