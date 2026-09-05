<?php

namespace App\Services;

use App\DTOs\CambioEstado;
use App\Models\EstadoFinanciero;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraHistorial;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de estado para el modelo OrdenCompra.
 *
 * Responsabilidad única: calcular y persistir los estados financieros
 * (ingreso + egreso) y el estado operativo resultante.
 */
class OrdenStateService
{
    /**
     * Evalúa los pagos de clientes y asigna el estado financiero de ingreso.
     */
    public static function sincronizarFinanciero(OrdenCompra $orden): CambioEstado
    {
        $totalPagado = $orden->pagos()->sum('monto_asignado');
        $totalFacturado = (float) $orden->monto_total;

        $slugEstado = 'parcial';
        if ($totalPagado <= 0) {
            $slugEstado = 'pendiente';
        } elseif ($totalPagado >= $totalFacturado) {
            $slugEstado = 'pagado';
        }

        $estado = Cache::remember("catalog.estado_financiero.{$slugEstado}", 86400,
            fn () => EstadoFinanciero::where('slug', $slugEstado)->first());

        if (! $estado) {
            return CambioEstado::sinCambio();
        }

        $cambio = CambioEstado::sinCambio();

        if ($orden->id_estado_financiero !== $estado->id) {
            $anterior = $orden->id_estado_financiero;
            $orden->update(['id_estado_financiero' => $estado->id]);
            $cambio = CambioEstado::conCambio($anterior, $estado->id);
        }

        // Reevaluar el egreso: si el cliente pagó el 100%, el egreso pasa
        // a "Pendiente por Facturar" (sincronizarEgreso también recalcula el operativo).
        self::sincronizarEgreso($orden->fresh());

        return $cambio;
    }

    /**
     * Evalúa los pagos a proveedores y asigna el estado financiero de egreso.
     */
    public static function sincronizarEgreso(OrdenCompra $orden): CambioEstado
    {
        $cuentas = $orden->cuentasPorPagar;

        if ($cuentas->isEmpty()) {
            $slugEstado = 'pendiente';
        } elseif (self::ingresoPagado($orden) && ! $orden->facturado_proveedor) {
            // Cliente pagó el 100% y aún no se factura a proveedores.
            $slugEstado = 'pendiente_facturacion';
        } else {
            $montoTotalDeuda = $cuentas->sum('monto_total');
            $saldoPendienteTotal = $cuentas->sum('saldo_pendiente');

            $slugEstado = match (true) {
                $saldoPendienteTotal <= 0 => 'pagado',
                $saldoPendienteTotal >= $montoTotalDeuda => 'pendiente',
                default => 'parcial',
            };
        }

        $estado = Cache::remember("catalog.estado_financiero.{$slugEstado}", 86400,
            fn () => EstadoFinanciero::where('slug', $slugEstado)->first());

        $cambio = CambioEstado::sinCambio();

        if ($estado && $orden->id_estado_financiero_egreso !== $estado->id) {
            $anterior = $orden->id_estado_financiero_egreso;
            $orden->update(['id_estado_financiero_egreso' => $estado->id]);
            $cambio = CambioEstado::conCambio($anterior, $estado->id);
        }

        self::sincronizarOperativo($orden->fresh());

        return $cambio;
    }

    /**
     * Sincroniza el estado operativo según el estado de ingresos y egresos.
     *
     * Es privado porque siempre se llama como consecuencia de un cambio
     * financiero, nunca directamente desde fuera.
     */
    private static function sincronizarOperativo(OrdenCompra $orden): CambioEstado
    {
        // Si la OC está anulada, no recalcular — la anulación es un estado terminal
        $idAnulada = Cache::remember('catalog.estado_orden_compra.anulada_id', 86400,
            fn () => EstadoOrdenCompra::where('slug', 'anulada')->value('id'));
        if ($idAnulada && (int) $orden->id_estado_orden_compra === (int) $idAnulada) {
            return CambioEstado::sinCambio();
        }

        $idPagado = Cache::remember('catalog.estado_financiero.pagado_id', 86400,
            fn () => EstadoFinanciero::where('slug', 'pagado')->value('id'));
        $idParcial = Cache::remember('catalog.estado_financiero.parcial_id', 86400,
            fn () => EstadoFinanciero::where('slug', 'parcial')->value('id'));

        $ingresoOk = (int) $orden->id_estado_financiero === (int) $idPagado;
        $egresoOk = (int) $orden->id_estado_financiero_egreso === (int) $idPagado;

        $hayMovimiento = in_array((int) $orden->id_estado_financiero, [(int) $idPagado, (int) $idParcial], true)
                      || in_array((int) $orden->id_estado_financiero_egreso, [(int) $idPagado, (int) $idParcial], true);

        if ($ingresoOk && $egresoOk) {
            $slug = 'completada';
            $msg = 'Completada automáticamente: ingresos y egresos liquidados al 100%';
        } elseif ($hayMovimiento) {
            $slug = 'en_proceso';
            $msg = 'En proceso: se registraron pagos parciales en ingresos o egresos';
        } else {
            $slug = 'pendiente';
            $msg = 'Revertida a pendiente: sin movimientos de pago registrados';
        }

        $estadoObjetivo = Cache::remember("catalog.estado_orden_compra.{$slug}", 86400,
            fn () => EstadoOrdenCompra::where('slug', $slug)->first());

        if (! $estadoObjetivo || $orden->id_estado_orden_compra === $estadoObjetivo->id) {
            return CambioEstado::sinCambio();
        }

        $anterior = $orden->id_estado_orden_compra;
        $orden->update(['id_estado_orden_compra' => $estadoObjetivo->id]);

        OrdenCompraHistorial::create([
            'orden_compra_id' => $orden->id,
            'id_estado_anterior' => $anterior,
            'id_estado_nuevo' => $estadoObjetivo->id,
            'usuario_id' => null,
            'comentario' => $msg,
        ]);

        Log::info("OrdenStateService: OC #{$orden->id} → {$slug}");

        return CambioEstado::conCambio($anterior, $estadoObjetivo->id, $msg);
    }

    /**
     * Indica si el cliente ya pagó el 100% de la Orden de Compra.
     */
    private static function ingresoPagado(OrdenCompra $orden): bool
    {
        $totalFacturado = (float) $orden->monto_total;
        if ($totalFacturado <= 0) {
            return false;
        }

        return (float) $orden->pagos()->sum('monto_asignado') >= $totalFacturado;
    }
}
