<?php

namespace App\Listeners;

use App\Events\CotizacionEstatusActualizado;
use App\Events\OrdenCompraAprobada;
use App\Models\OrdenCompra;
use App\Models\EstadoCotizacion;
use App\Models\EstadoOrdenCompra;
use Illuminate\Support\Facades\Log;

class GenerarOrdenDesdeCotizacionListener
{
    /**
     * Maneja el evento de cambio de estatus de la cotización.
     * Si el nuevo estatus es "aprobada", genera automáticamente la Orden de Compra.
     */
    public function handle(CotizacionEstatusActualizado $event): void
    {
        $cotizacion = $event->cotizacion;
        $idNuevoEstatus = $event->estatusNuevo;

        // 1. Verificar si el nuevo estado es "aprobada" en el catálogo
        $estatusAprobado = EstadoCotizacion::where('slug', 'aprobada')->first();

        if (!$estatusAprobado || $idNuevoEstatus != $estatusAprobado->id) {
            return;
        }

        // 2. Evitar duplicados: solo crear si no existe una OC para esta cotización
        if (OrdenCompra::where('id_cotizacion', $cotizacion->id)->exists()) {
            return;
        }

        // 3. Obtener el estado inicial "pendiente" del nuevo catálogo
        $estadoPendiente = EstadoOrdenCompra::where('slug', 'pendiente')->firstOrFail();

        // 4. Calcular monto total desde los servicios de la cotización
        $montoTotal = $cotizacion->servicios()->sum('total_servicio');

        // 5. Crear la Orden de Compra apuntando al catálogo propio
        $orden = OrdenCompra::create([
            'id_cotizacion'               => $cotizacion->id,
            'monto_total'                 => $montoTotal,
            'id_estado_orden_compra'      => $estadoPendiente->id,
            'id_estado_financiero'        => 1, // Pendiente por cobrar
            'id_estado_financiero_egreso' => 1, // Pendiente por pagar
        ]);

        Log::info("Orden de Compra #{$orden->id} generada automáticamente desde Cotización #{$cotizacion->id} por monto de {$montoTotal}");

        // 6. Disparar evento de aprobada para generar las Cuentas por Pagar
        event(new OrdenCompraAprobada($orden));
    }
}
