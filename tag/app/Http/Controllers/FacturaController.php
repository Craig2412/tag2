<?php

namespace App\Http\Controllers;

use App\Models\Factura;
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

    /**
     * Listar las facturas emitidas
     *
     * Devuelve el listado de facturas persistidas con su cliente y orden de compra.
     */
    public function index()
    {
        $facturas = Factura::with(['cliente', 'ordenCompra'])
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $facturas]);
    }

    /**
     * Obtener las facturas de una orden de compra
     *
     * Devuelve las facturas emitidas asociadas a la orden de compra indicada,
     * con sus detalles por servicio y retenciones congeladas.
     */
    public function show(OrdenCompra $ordenCompra)
    {
        $facturas = Factura::where('id_orden_compra', $ordenCompra->id)
            ->with(['cliente', 'ordenCompra', 'detalles', 'retenciones'])
            ->get();

        return response()->json(['data' => $facturas]);
    }

    /**
     * Emitir (persistir) la factura de una orden de compra
     *
     * Calcula y congela la factura en la base de datos.
     */
    public function emitir(OrdenCompra $ordenCompra)
    {
        $ordenCompra->load('cotizacion.servicios.tipoServicio', 'cotizacion.servicios.proveedor');

        $factura = $this->facturaService->emitir($ordenCompra, auth()->id());

        $factura->load(['cliente', 'ordenCompra', 'detalles', 'retenciones']);

        return response()->json(['data' => $factura], 201);
    }
}
