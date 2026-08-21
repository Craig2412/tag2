<?php

namespace App\Services;

use App\Models\ConceptoFiscal;
use App\Models\OrdenCompra;
use Illuminate\Support\Collection;

/**
 * Calcula el desglose fiscal (factura) de una Orden de Compra.
 *
 * Reglas:
 * - Se calcula servicio por servicio.
 * - La "base gravable" es monto_gravable y el "exento" es monto_no_sujeto.
 * - El IVA se obtiene de iva_establecido de cada servicio.
 * - Los conceptos fiscales (impuestos/retenciones) son configurables en BD
 *   (tabla conceptos_fiscales): quién los aplica, sobre qué base y en qué %.
 */
class FacturaService
{
    /**
     * Genera el desglose completo de la factura de una Orden de Compra.
     */
    public function generarFactura(OrdenCompra $orden): array
    {
        $servicios = $orden->cotizacion->servicios()
            ->with(['tipoServicio', 'proveedor'])
            ->get();

        $conceptos = ConceptoFiscal::activos()->get();

        $detalle = [];
        $totalBase = 0.0;
        $totalExento = 0.0;
        $totalIva = 0.0;
        $totalFacturado = 0.0;
        $totalRetencionesCliente = 0.0;

        foreach ($servicios as $servicio) {
            $fila = $this->calcularServicio($servicio, $conceptos->where('aplica_a', 'cliente'));

            $detalle[] = $fila;

            $totalBase += $fila['base'];
            $totalExento += $fila['exento'];
            $totalIva += $fila['iva_valor'];
            $totalFacturado += $fila['total_facturado'];
            $totalRetencionesCliente += $fila['subtotal_retenciones'];
        }

        return [
            'orden_compra_id' => $orden->id,
            'moneda' => 'USD',
            'resumen' => [
                'total_base_gravable' => $this->round($totalBase),
                'total_exento' => $this->round($totalExento),
                'total_iva' => $this->round($totalIva),
                'total_facturado' => $this->round($totalFacturado),
                'total_retenciones_cliente' => $this->round($totalRetencionesCliente),
                'total_a_pagar_cliente' => $this->round($totalFacturado - $totalRetencionesCliente),
            ],
            'detalle_servicios' => $detalle,
        ];
    }

    /**
     * Calcula las retenciones que aplica la empresa sobre una Orden de Compra
     * y el total neto que le queda a la empresa.
     */
    public function calcularRetencionesEmpresa(OrdenCompra $orden): array
    {
        $servicios = $orden->cotizacion->servicios()->get();

        $conceptos = ConceptoFiscal::activos()
            ->where('aplica_a', 'empresa')
            ->get();

        $detalle = [];
        $totales = [];

        foreach ($conceptos as $concepto) {
            $totales[$concepto->codigo] = 0.0;
        }

        $totalFacturado = 0.0;
        $totalRetencionesCliente = 0.0;
        $totalRetencionesEmpresa = 0.0;

        foreach ($servicios as $servicio) {
            $base = (float) $servicio->monto_gravable;
            $ivaValor = $base * ((float) $servicio->iva_establecido / 100);
            $exento = (float) $servicio->monto_no_sujeto;
            $nombre = $servicio->tipoServicio->tipo_servicio ?? $servicio->descripcion ?? ('Servicio #' . $servicio->id);

            $filaServicio = [
                'servicio_id' => $servicio->id,
                'servicio' => $nombre,
                'base' => $this->round($base),
                'iva_valor' => $this->round($ivaValor),
                'total_facturado' => $this->round($base + $ivaValor + $exento),
                'retenciones' => [],
                'total_retenciones_empresa' => 0.0,
            ];

            $sumaRets = 0.0;
            foreach ($conceptos as $concepto) {
                $monto = $this->calcularMontoConcepto($concepto, $base, $ivaValor, $nombre);
                $filaServicio['retenciones'][$concepto->nombre] = $this->round($monto);
                $totales[$concepto->codigo] += $monto;
                $sumaRets += $monto;
            }

            $filaServicio['total_retenciones_empresa'] = $this->round($sumaRets);
            $detalle[] = $filaServicio;

            $totalFacturado += $base + $ivaValor + $exento;
            $totalRetencionesEmpresa += $sumaRets;

            // Retenciones de cliente (necesarias para el neto final)
            $conceptosCliente = ConceptoFiscal::activos()->where('aplica_a', 'cliente')->get();
            foreach ($conceptosCliente as $c) {
                $totalRetencionesCliente += $this->calcularMontoConcepto($c, $base, $ivaValor, $nombre);
            }
        }

        $totalesRedondeados = [];
        foreach ($totales as $codigo => $monto) {
            $totalesRedondeados[$codigo] = $this->round($monto);
        }

        return [
            'orden_compra_id' => $orden->id,
            'resumen' => [
                'total_facturado' => $this->round($totalFacturado),
                'total_retenciones_cliente' => $this->round($totalRetencionesCliente),
                'total_retenciones_empresa' => $this->round($totalRetencionesEmpresa),
                'total_neto_empresa' => $this->round($totalFacturado - $totalRetencionesCliente - $totalRetencionesEmpresa),
            ],
            'totales_por_concepto' => $totalesRedondeados,
            'detalle_servicios' => $detalle,
        ];
    }

    /**
     * Calcula el desglose completo de un único servicio.
     */
    private function calcularServicio($servicio, Collection $conceptos): array
    {
        $base = (float) $servicio->monto_gravable;
        $exento = (float) $servicio->monto_no_sujeto;
        $ivaPct = (float) $servicio->iva_establecido;
        $ivaValor = $base * ($ivaPct / 100);

        $nombre = $servicio->tipoServicio->tipo_servicio ?? $servicio->descripcion ?? ('Servicio #' . $servicio->id);

        $totalFacturado = $base + $ivaValor + $exento;

        $retenciones = [];
        $subtotalRets = 0.0;

        foreach ($conceptos as $concepto) {
            $monto = $this->calcularMontoConcepto($concepto, $base, $ivaValor, $nombre);
            $retenciones[] = [
                'concepto' => $concepto->nombre,
                'codigo' => $concepto->codigo,
                'base_calculo' => $concepto->base_calculo,
                'porcentaje' => $concepto->porcentaje,
                'monto' => $this->round($monto),
            ];
            $subtotalRets += $monto;
        }

        return [
            'servicio_id' => $servicio->id,
            'servicio' => $nombre,
            'base' => $this->round($base),
            'exento' => $this->round($exento),
            'iva_porcentaje' => $ivaPct,
            'iva_valor' => $this->round($ivaValor),
            'total_facturado' => $this->round($totalFacturado),
            'retenciones' => $retenciones,
            'subtotal_retenciones' => $this->round($subtotalRets),
            'total_a_pagar' => $this->round($totalFacturado - $subtotalRets),
        ];
    }

    /**
     * Calcula el monto de un concepto fiscal para un servicio dado.
     */
    private function calcularMontoConcepto(ConceptoFiscal $concepto, float $base, float $ivaValor, string $nombreServicio): float
    {
        // Exclusión por palabra clave (ej. ISLR cliente no aplica a "boleto")
        if ($concepto->excluir_si_contiene) {
            if (mb_stripos(mb_strtolower($nombreServicio), mb_strtolower($concepto->excluir_si_contiene)) !== false) {
                return 0.0;
            }
        }

        $baseCalculo = $concepto->base_calculo === 'valor_iva' ? $ivaValor : $base;

        return $baseCalculo * ((float) $concepto->porcentaje / 100);
    }

    private function round(float $value): float
    {
        return round($value, 2);
    }
}
