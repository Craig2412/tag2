<?php

namespace App\Services;

use App\Models\Atencion;
use App\Models\EtapaComercial;
use App\Models\OrdenCompra;
use App\Models\EstadoFinanciero;

class EstadoFaseService
{
    /**
     * Evalúa las relaciones de la Atención y le asigna la fase comercial correcta
     * (atencion, cotizada, orden_compra) basada en el catálogo dinámico.
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

        if (!$etapa) {
            return; // No catalog found, do not break application
        }

        // 3. Sincronizar físicamente en la BD si hubo un cambio
        if ($atencion->id_etapa_comercial !== $etapa->id) {
            $etapaAnterior = $atencion->id_etapa_comercial;
            $atencion->updateQuietly(['id_etapa_comercial' => $etapa->id]);
            
            // Disparar evento para registrar historial
            event(new \App\Events\AtencionEtapaCambiada($atencion, $etapaAnterior, $etapa->id));
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
    }
}
