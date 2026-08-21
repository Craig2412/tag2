<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Services\FacturaService;

/**
 * Generación de facturas fiscales y cálculo de retenciones
 * asociados a las Órdenes de Compra.
 */
class FacturaController extends Controller
{
    public function __construct(private readonly FacturaService $facturaService) {}

    /**
     * Generar la factura fiscal de una orden de compra
     *
     * Calcula servicio por servicio: base gravable, exento, IVA,
     * retenciones del cliente y total a pagar.
     */
    public function factura(OrdenCompra $ordenCompra)
    {
        $ordenCompra->load('cotizacion.servicios.tipoServicio', 'cotizacion.servicios.proveedor');

        return response()->json([
            'data' => $this->facturaService->generarFactura($ordenCompra),
        ]);
    }

    /**
     * Calcular las retenciones de la empresa sobre una orden de compra
     *
     * Retorna el desglose por servicio de las retenciones (Alcaldía, ISLR,
     * INATUR, IVA) y el total neto que le queda a la empresa.
     */
    public function retenciones(OrdenCompra $ordenCompra)
    {
        $ordenCompra->load('cotizacion.servicios.tipoServicio');

        return response()->json([
            'data' => $this->facturaService->calcularRetencionesEmpresa($ordenCompra),
        ]);
    }
}
