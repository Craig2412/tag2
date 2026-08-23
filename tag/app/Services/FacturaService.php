<?php

namespace App\Services;

use App\Models\ConceptoFiscal;
use App\Models\OrdenCompra;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Emite (persiste) la factura fiscal de una Orden de Compra.
     *
     * Congela cabecera, detalles por servicio y retenciones.
     * Devuelve el modelo Factura persistido.
     */
    public function emitir(OrdenCompra $orden, ?int $usuarioEmiteId = null): \App\Models\Factura
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($orden, $usuarioEmiteId) {
            // 1. Si ya existe factura para esta OC, devolverla (idempotente)
            $existente = \App\Models\Factura::where('id_orden_compra', $orden->id)->first();
            if ($existente) {
                return $existente;
            }

            // 2. Calcular desglose completo
            $facturaData = $this->generarFactura($orden);
            $empresaData = $this->calcularRetencionesEmpresa($orden);

            $resumen = $facturaData['resumen'];
            $resumenEmpresa = $empresaData['resumen'];

            // 3. Determinar cliente desde la atención de la cotización
            $cliente = $orden->cotizacion?->atencion?->cliente;

            // 4. Datos del emisor (primera empresa registrada, si existe)
            $emisor = \App\Models\Empresa::query()->first();

            // 5. Numeración secuencial: serie A + correlativo por año
            [$numero, $anio, $correlativo] = $this->siguienteNumeroFactura();

            // 6. Crear la factura (cabecera)
            $factura = \App\Models\Factura::create([
                'numero_factura' => $numero,
                'id_orden_compra' => $orden->id,
                'id_cliente' => $cliente?->id,
                'emisor_rif' => $emisor?->rif,
                'emisor_razon_social' => $emisor?->razon_social,
                'timbrado' => null,
                'total_gravable' => $resumen['total_base_gravable'],
                'total_exento' => $resumen['total_exento'],
                'total_iva' => $resumen['total_iva'],
                'total_facturado' => $resumen['total_facturado'],
                'total_retenciones_cliente' => $resumen['total_retenciones_cliente'],
                'total_retenciones_empresa' => $resumenEmpresa['total_retenciones_empresa'],
                'total_a_pagar' => $resumen['total_a_pagar_cliente'],
                'total_neto_empresa' => $resumenEmpresa['total_neto_empresa'],
                'anio' => $anio,
                'correlativo' => $correlativo,
                'usuario_emite_id' => $usuarioEmiteId,
                'fecha_emision' => now(),
            ]);

            // 7. Crear detalles por servicio
            foreach ($facturaData['detalle_servicios'] as $detalle) {
                $detalleModel = $factura->detalles()->create([
                    'id_servicio' => $detalle['servicio_id'],
                    'descripcion_servicio' => $detalle['servicio'],
                    'base_gravable' => $detalle['base'],
                    'monto_no_sujeto' => $detalle['exento'],
                    'iva_porcentaje' => $detalle['iva_porcentaje'],
                    'iva_valor' => $detalle['iva_valor'],
                    'total_servicio' => $detalle['total_facturado'],
                    'total_retenciones_servicio' => $detalle['subtotal_retenciones'],
                    'total_a_pagar_servicio' => $detalle['total_a_pagar'],
                ]);

                // 8. Retenciones del CLIENTE por servicio (congeladas)
                foreach ($detalle['retenciones'] as $ret) {
                    $factura->retenciones()->create([
                        'id_factura_detalle' => $detalleModel->id,
                        'codigo_concepto' => $ret['codigo'],
                        'nombre_concepto' => $ret['concepto'],
                        'aplica_a' => 'cliente',
                        'base_calculo' => $ret['base_calculo'],
                        'porcentaje' => $ret['porcentaje'],
                        'monto' => $ret['monto'],
                    ]);
                }
            }

            // 9. Retenciones de la EMPRESA por servicio (congeladas)
            foreach ($empresaData['detalle_servicios'] as $detalleEmp) {
                $idServicio = $detalleEmp['servicio_id'];
                // Ubicar el detalle de factura correspondiente a este servicio
                $detalleFactura = $factura->detalles()
                    ->where('id_servicio', $idServicio)
                    ->first();

                foreach ($detalleEmp['retenciones'] as $nombreConcepto => $monto) {
                    $factura->retenciones()->create([
                        'id_factura_detalle' => $detalleFactura?->id,
                        'codigo_concepto' => $this->codigoEmpresaDesdeNombre($nombreConcepto),
                        'nombre_concepto' => $nombreConcepto,
                        'aplica_a' => 'empresa',
                        'base_calculo' => 'base_gravable',
                        'porcentaje' => $this->porcentajeEmpresaDesdeNombre($nombreConcepto),
                        'monto' => $monto,
                    ]);
                }
            }

            return $factura;
        });
    }

    /**
     * Devuelve el siguiente número de factura (serie "A" + correlativo de 8 dígitos por año).
     */
    private function siguienteNumeroFactura(): array
    {
        $anio = now()->format('Y');
        $ultima = \App\Models\Factura::where('anio', $anio)
            ->orderByDesc('correlativo')
            ->first();

        $correlativo = $ultima ? ($ultima->correlativo + 1) : 1;

        $numero = 'A-' . str_pad((string) $correlativo, 8, '0', STR_PAD_LEFT);

        return [$numero, $anio, $correlativo];
    }

    private function codigoEmpresaDesdeNombre(string $nombre): string
    {
        $mapa = [
            'Alcaldía' => 'alcaldia_empresa',
            'ISLR' => 'islr_empresa',
            'INATUR' => 'inatur_empresa',
            'IVA' => 'retencion_iva_empresa',
        ];

        return $mapa[$nombre] ?? strtolower($nombre);
    }

    private function porcentajeEmpresaDesdeNombre(string $nombre): float
    {
        $mapa = [
            'Alcaldía' => 2.2,
            'ISLR' => 1.0,
            'INATUR' => 1.0,
            'IVA' => 25.0,
        ];

        return $mapa[$nombre] ?? 0.0;
    }
}
