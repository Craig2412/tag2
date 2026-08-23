<?php

namespace App\Listeners;

use App\Events\CotizacionEstatusActualizado;
use App\Events\OrdenCompraAprobada;
use App\Models\Cotizacion;
use App\Models\EstadoCotizacion;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Services\OrdenStateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerarOrdenDesdeCotizacionListener
{
    /**
     * Maneja el evento de cambio de estatus de la cotización.
     *
     * - Si el nuevo estatus es "aprobada", genera automáticamente la Orden de Compra.
     * - Si el nuevo estatus es "rechazada", anula la Orden de Compra existente
     *   y limpia sus Cuentas por Pagar (simétrico a la aprobación).
     */
    public function handle(CotizacionEstatusActualizado $event): void
    {
        $cotizacion = $event->cotizacion;
        $idNuevoEstatus = $event->estatusNuevo;

        // ── Caso 1: APROBADA → crear OC ──────────────────────────
        $estatusAprobado = Cache::remember('catalog.estado_cotizacion.aprobada', 86400,
            fn () => EstadoCotizacion::where('slug', 'aprobada')->first());

        if ($estatusAprobado && $idNuevoEstatus == $estatusAprobado->id) {
            $this->crearOrdenCompra($cotizacion);
            return;
        }

        // ── Caso 2: RECHAZADA → anular OC y limpiar CxP ──────────
        $estatusRechazado = Cache::remember('catalog.estado_cotizacion.rechazada', 86400,
            fn () => EstadoCotizacion::where('slug', 'rechazada')->first());

        if ($estatusRechazado && $idNuevoEstatus == $estatusRechazado->id) {
            $this->anularOrdenCompra($cotizacion);
        }
    }

    /**
     * Crea la Orden de Compra a partir de una cotización aprobada.
     */
    private function crearOrdenCompra(Cotizacion $cotizacion): void
    {
        if (OrdenCompra::where('id_cotizacion', $cotizacion->id)->exists()) {
            return;
        }

        $estadoPendiente = Cache::remember('catalog.estado_orden_compra.pendiente', 86400,
            fn () => EstadoOrdenCompra::where('slug', 'pendiente')->firstOrFail());

        $montoTotal = $cotizacion->servicios()->sum('total_servicio');

        $estadoFinancieroPendiente = Cache::remember('catalog.estado_financiero.pendiente', 86400,
            fn () => \App\Models\EstadoFinanciero::where('slug', 'pendiente')->firstOrFail());

        $orden = OrdenCompra::create([
            'id_cotizacion' => $cotizacion->id,
            'monto_total' => $montoTotal,
            'id_estado_orden_compra' => $estadoPendiente->id,
            'id_estado_financiero' => $estadoFinancieroPendiente->id,
            'id_estado_financiero_egreso' => $estadoFinancieroPendiente->id,
        ]);

        Log::info("Orden de Compra #{$orden->id} generada automáticamente desde Cotización #{$cotizacion->id} por monto de {$montoTotal}");

        // Emitir factura fiscal automáticamente al crear la orden de compra
        try {
            $factura = app(\App\Services\FacturaService::class)->emitir($orden->fresh());
            Log::info("Factura #{$factura->numero_factura} emitida automáticamente para OC #{$orden->id}");
        } catch (\Throwable $e) {
            Log::error("Error emitiendo factura para OC #{$orden->id}: {$e->getMessage()}");
        }

        event(new OrdenCompraAprobada($orden));
    }

    /**
     * Anula la Orden de Compra y limpia sus Cuentas por Pagar
     * cuando la cotización pasa a "rechazada".
     *
     * Replica la lógica de OrdenCompraObserver::deleted() pero sin
     * soft-delete de la OC (solo cambia su estado a "anulada").
     */
    private function anularOrdenCompra(Cotizacion $cotizacion): void
    {
        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();

        if (! $orden) {
            return;
        }

        // 1. Limpiar cuentas por pagar y sus pivotes de pago a proveedores
        $orden->limpiarCuentasPorPagar();

        // 2. Sincronizar el estado de egreso (quedará como pendiente sin CxP)
        OrdenStateService::sincronizarEgreso($orden->fresh());

        // 3. Cambiar el estado operativo de la OC a "anulada"
        //    (debe ir DESPUÉS del sync para que no lo revierta sincronizarOperativo)
        $estadoAnulada = Cache::remember('catalog.estado_orden_compra.anulada', 86400,
            fn () => EstadoOrdenCompra::where('slug', 'anulada')->first());

        if ($estadoAnulada) {
            $orden->update(['id_estado_orden_compra' => $estadoAnulada->id]);
        }

        Log::info("Orden de Compra #{$orden->id} anulada por rechazo de Cotización #{$cotizacion->id}");
    }
}
