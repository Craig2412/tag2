<?php

namespace App\Services;

use App\Models\Atencion;
use App\Models\EtapaComercial;
use App\Models\EstadoAtencion;
use App\Models\OrdenCompra;
use App\Models\EstadoFinanciero;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompraHistorial;
use Illuminate\Support\Facades\Log;
use App\Events\AtencionEtapaCambiada;
use App\Events\AtencionEstatusActualizado;

class EstadoFaseService
{
    /**
     * Evalúa las relaciones de la Atención y le asigna la fase comercial correcta
     * (atencion, cotizada, orden_compra) basada en el catálogo dinámico,
     * y si llega a orden_compra, la cierra como ganada.
     */
    public static function sincronizarFaseAtencion(Atencion $atencion): void
    {
        // 1. Decidir la fase correcta basándose en la lógica cruda
        $tieneCotizaciones = $atencion->cotizaciones()->exists();
        
        $tieneOrdenes = false;
        if ($tieneCotizaciones) {
            $tieneOrdenes = OrdenCompra::whereIn('id_cotizacion', $atencion->cotizaciones()->pluck('id'))->exists();
        }

        $slugFase = 'atencion';
        if ($tieneOrdenes) {
            $slugFase = 'orden_compra';
        } elseif ($tieneCotizaciones) {
            $slugFase = 'cotizada';
        }

        // 2. Buscar en el catálogo
        $etapa = EtapaComercial::where('slug', $slugFase)->first();
        Log::info("Sincronizando Atencion #{$atencion->id}. Fase calculada: {$slugFase}. Etapa ID: " . ($etapa ? $etapa->id : 'null'));

        if (!$etapa) {
            return; // No catalog found, do not break application
        }

        // 3. Sincronizar físicamente en la BD si hubo un cambio de etapa
        $huboCambio = false;
        
        if ($atencion->id_etapa_comercial !== $etapa->id) {
            Log::info("Cambiando etapa de Atencion #{$atencion->id} a {$etapa->id}");
            $etapaAnterior = $atencion->id_etapa_comercial;
            $atencion->id_etapa_comercial = $etapa->id;
            $huboCambio = true;
            
            // Disparar evento para registrar historial de etapa
            event(new AtencionEtapaCambiada($atencion, $etapaAnterior, $etapa->id));
        }

        // 4. Si la etapa es orden_compra, el estado de la atención debe ser "cerrada_ganada"
        if ($slugFase === 'orden_compra') {
            $estadoCerradaGanada = EstadoAtencion::where('slug', 'cerrada_ganada')->first();
            if ($estadoCerradaGanada && $atencion->id_estado_atencion !== $estadoCerradaGanada->id) {
                Log::info("Cerrando ganada la Atencion #{$atencion->id} porque tiene orden de compra");
                
                $estadoAnterior = $atencion->id_estado_atencion;
                $atencion->id_estado_atencion = $estadoCerradaGanada->id;
                $huboCambio = true;

                // Si hay un evento para el estado (ej. AtencionEstatusActualizado), podríamos dispararlo aquí
                event(new AtencionEstatusActualizado($atencion, $estadoAnterior, $estadoCerradaGanada->id, 'Cerrada ganada automáticamente por Orden de Compra'));
            }
        }

        if ($huboCambio) {
            $atencion->saveQuietly();
        }
    }

    /**
     * Evalúa los pagos de una Orden y le asigna el estado financiero correcto
     * (pendiente, parcial, pagado) basado en el catálogo dinámico.
     */
    public static function sincronizarEstadoFinanciero(OrdenCompra $orden): void
    {
        $totalPagado = $orden->pagos()->sum('monto_asignado');
        $totalFacturado = (float) $orden->monto_total;

        $slugEstado = 'parcial';
        if ($totalPagado <= 0) {
            $slugEstado = 'pendiente';
        } elseif ($totalPagado >= $totalFacturado) {
            $slugEstado = 'pagado';
        }

        // 2. Buscar en el catálogo
        $estado = EstadoFinanciero::where('slug', $slugEstado)->first();

        if (!$estado) {
            return;
        }

        // 3. Sincronizar físicamente en la BD si hubo un cambio
        if ($orden->id_estado_financiero !== $estado->id) {
            $orden->updateQuietly(['id_estado_financiero' => $estado->id]);
        }

        // 4. Evaluar si la OC debe marcarse como "completada" operativamente
        self::sincronizarEstadoOperativo($orden->fresh());
    }

    /**
     * Evalúa los pagos realizados a proveedores y le asigna el estado de egreso correcto.
     */
    public static function sincronizarEstadoEgreso(OrdenCompra $orden): void
    {
        $cuentas = $orden->cuentasPorPagar;
        
        if ($cuentas->isEmpty()) {
            // Si no hay cuentas por pagar aún, lo dejamos como pendiente
            $slugEstado = 'pendiente';
        } else {
            $montoTotalDeuda = $cuentas->sum('monto_total');
            $saldoPendienteTotal = $cuentas->sum('saldo_pendiente');

            if ($saldoPendienteTotal <= 0) {
                $slugEstado = 'pagado';
            } elseif ($saldoPendienteTotal >= $montoTotalDeuda) {
                $slugEstado = 'pendiente';
            } else {
                $slugEstado = 'parcial';
            }
        }

        $estado = EstadoFinanciero::where('slug', $slugEstado)->first();

        if ($estado && $orden->id_estado_financiero_egreso !== $estado->id) {
            $orden->updateQuietly(['id_estado_financiero_egreso' => $estado->id]);
        }

        // Evaluar si la OC debe marcarse como "completada" operativamente
        self::sincronizarEstadoOperativo($orden->fresh());
    }

    /**
     * Sincroniza el estado operativo de la OC según los pagos registrados:
     * - completada  → ingreso Y egreso liquidados al 100%
     * - en_proceso  → hay pagos parciales en alguno de los dos
     * - pendiente   → sin ningún movimiento de dinero
     */
    public static function sincronizarEstadoOperativo(OrdenCompra $orden): void
    {
        $estadoPagado  = EstadoFinanciero::where('slug', 'pagado')->value('id');
        $estadoParcial = EstadoFinanciero::where('slug', 'parcial')->value('id');

        $ingresoLiquidado = (int) $orden->id_estado_financiero        === (int) $estadoPagado;
        $egresoLiquidado  = (int) $orden->id_estado_financiero_egreso === (int) $estadoPagado;

        $hayMovimiento = in_array((int) $orden->id_estado_financiero,        [(int) $estadoPagado, (int) $estadoParcial], true)
                      || in_array((int) $orden->id_estado_financiero_egreso, [(int) $estadoPagado, (int) $estadoParcial], true);

        if ($ingresoLiquidado && $egresoLiquidado) {
            $slugObjetivo = 'completada';
            $comentario   = 'Completada automáticamente: ingresos y egresos liquidados al 100%';
        } elseif ($hayMovimiento) {
            $slugObjetivo = 'en_proceso';
            $comentario   = 'En proceso: se registraron pagos parciales en ingresos o egresos';
        } else {
            $slugObjetivo = 'pendiente';
            $comentario   = 'Revertida a pendiente: sin movimientos de pago registrados';
        }

        $estadoObjetivo = EstadoOrdenCompra::where('slug', $slugObjetivo)->first();

        if (!$estadoObjetivo || $orden->id_estado_orden_compra === $estadoObjetivo->id) {
            return;
        }

        $estadoAnteriorId = $orden->id_estado_orden_compra;
        $orden->updateQuietly(['id_estado_orden_compra' => $estadoObjetivo->id]);

        OrdenCompraHistorial::create([
            'orden_compra_id'    => $orden->id,
            'id_estado_anterior' => $estadoAnteriorId,
            'id_estado_nuevo'    => $estadoObjetivo->id,
            'usuario_id'         => null,
            'comentario'         => $comentario,
        ]);

        Log::info("OrdenCompra #{$orden->id} marcada como '{$slugObjetivo}' automáticamente.");
    }
}
