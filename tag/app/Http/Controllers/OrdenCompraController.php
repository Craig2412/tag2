<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrdenCompraResource;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraHistorial;
use App\Services\OrdenStateService;
use Illuminate\Http\Request;

class OrdenCompraController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OrdenCompra::class, 'ordenCompra');
    }

    /**
     * Listar todas las órdenes de compra
     *
     * Devuelve todas las órdenes con su cotización, tasa de cambio, estado operativo y financiero.
     */
    public function index()
    {
        return OrdenCompraResource::collection(
            OrdenCompra::with([
                'cotizacion.tasaCambio',
                'cotizacion.atencion',
                'estadoFinanciero',
                'estadoFinancieroEgreso',
                'estadoOrdenCompra',
            ])->orderBy('id')->get()
        );
    }

    /**
     * Obtener una orden de compra específica
     *
     * Devuelve detalles completos incluyendo servicios, tasa de cambio, pagos,
     * estados operativos y financieros, y cuentas por pagar.
     */
    public function show(OrdenCompra $ordenCompra)
    {
        $ordenCompra->recalcularMontoTotal();

        $ordenCompra->load([
            'cotizacion.servicios.proveedor',
            'cotizacion.servicios.tipoServicio',
            'cotizacion.atencion',
            'cotizacion.tasaCambio',
            'pagos.pago.metodoPago',
            'pagos.pago.entidadBancaria',
            'estadoFinanciero',
            'estadoFinancieroEgreso',
            'estadoOrdenCompra',
            'cuentasPorPagar.proveedor',
            'cuentasPorPagar.estadoFinanciero',
        ]);

        return new OrdenCompraResource($ordenCompra);
    }

    /**
     * Actualizar el estado operativo de una orden de compra
     *
     * Solo permite transiciones de estado válidas del catálogo estados_ordenes_compra.
     *
     * @bodyParam id_estado_orden_compra int required ID del estado operativo. Ejemplo: 2
     */
    public function update(Request $request, OrdenCompra $ordenCompra)
    {
        $data = $request->validate([
            'id_estado_orden_compra' => ['sometimes', 'required', 'exists:estados_ordenes_compra,id'],
        ]);

        $estadoAnteriorId = $ordenCompra->id_estado_orden_compra;

        $ordenCompra->update($data);
        $ordenCompra->recalcularMontoTotal();

        // Registrar en historial si cambió el estado operativo
        if (isset($data['id_estado_orden_compra']) && $data['id_estado_orden_compra'] != $estadoAnteriorId) {
            OrdenCompraHistorial::create([
                'orden_compra_id' => $ordenCompra->id,
                'id_estado_anterior' => $estadoAnteriorId,
                'id_estado_nuevo' => $data['id_estado_orden_compra'],
                'usuario_id' => auth()->id(),
                'comentario' => 'Cambio de estado desde API',
            ]);
        }

        return new OrdenCompraResource($ordenCompra->fresh()->load([
            'cotizacion.tasaCambio',
            'cotizacion.atencion',
            'estadoFinanciero',
            'estadoFinancieroEgreso',
            'estadoOrdenCompra',
        ]));
    }

    /**
     * Marca a los proveedores de una orden de compra como facturados (por lote)
     *
     * No registra facturas: solo cambia el marcador `facturado_proveedor` y
     * deja que el OrdenStateService recalcule el estado de egreso.
     */
    public function facturarProveedores(OrdenCompra $ordenCompra)
    {
        $this->authorize('facturarProveedores', $ordenCompra);

        $ordenCompra->update(['facturado_proveedor' => true]);

        OrdenStateService::sincronizarEgreso($ordenCompra->fresh());

        return new OrdenCompraResource($ordenCompra->fresh()->load([
            'cotizacion.tasaCambio',
            'cotizacion.atencion',
            'estadoFinanciero',
            'estadoFinancieroEgreso',
            'estadoOrdenCompra',
        ]));
    }

    /**
     * Eliminar una orden de compra
     *
     * Elimina (soft delete) la orden de compra del sistema.
     */
    public function destroy(OrdenCompra $ordenCompra)
    {
        $ordenCompra->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
