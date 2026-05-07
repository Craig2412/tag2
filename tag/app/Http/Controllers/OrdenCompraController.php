<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompraHistorial;
use App\Http\Resources\OrdenCompraResource;
use Illuminate\Http\Request;

class OrdenCompraController extends Controller
{
    /**
     * Listar todas las órdenes de compra
     *
     * Devuelve todas las órdenes con su cotización, tasa de cambio y estado operativo.
     */
    public function index()
    {
        return OrdenCompraResource::collection(
            OrdenCompra::with([
                'cotizacion.tasaCambio',
                'estadoFinanciero',
                'estadoOrdenCompra',
            ])->orderBy('id')->get()
        );
    }

    /**
     * Obtener una orden de compra específica
     *
     * Devuelve detalles completos incluyendo servicios, tasa de cambio, pagos y estados.
     */
    public function show(OrdenCompra $ordenCompra)
    {
        $ordenCompra->recalcularMontoTotal();

        $ordenCompra->load([
            'cotizacion.servicios',
            'cotizacion.tasaCambio',
            'pagos',
            'estadoFinanciero',
            'estadoOrdenCompra',
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
                'orden_compra_id'  => $ordenCompra->id,
                'id_estado_anterior' => $estadoAnteriorId,
                'id_estado_nuevo'    => $data['id_estado_orden_compra'],
                'usuario_id'         => auth()->id(),
                'comentario'         => 'Cambio de estado desde API',
            ]);
        }

        return new OrdenCompraResource($ordenCompra->fresh());
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
