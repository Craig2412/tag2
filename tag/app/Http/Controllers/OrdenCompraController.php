<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use Illuminate\Http\Request;

class OrdenCompraController extends Controller
{
    /**
     * Listar todas las órdenes de compra
     *
     * Devuelve todas las órdenes de compra con su cotización y tasa de cambio asociadas.
     */
    public function index()
    {
        return response()->json(OrdenCompra::with(['cotizacion.tasaCambio', 'estadoFinanciero'])->orderBy('id')->get());
    }

    /**
     * Crear una nueva orden de compra
     *
     * Genera una orden de compra a partir de una cotización confirmada. Solo se permite una orden por cotización.
     *
     * @bodyParam id_cotizacion int required ID de la cotización confirmada. Ejemplo: 1
     * @bodyParam estatus int ID del estatus de la orden (por defecto: pendiente de pago). Ejemplo: 1
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cotizacion' => ['required', 'exists:cotizaciones,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
        ]);

        if (OrdenCompra::where('id_cotizacion', $data['id_cotizacion'])->exists()) {
            return response()->json(['message' => 'La cotizacion ya tiene una orden de compra'], 422);
        }

        $cotizacion = Cotizacion::find($data['id_cotizacion']);
        $estatusConfirmado = Estatus::firstOrCreate(['estatus' => 'confirmado']);

        if (!$cotizacion || $cotizacion->estatus !== $estatusConfirmado->id) {
            return response()->json(['message' => 'Solo se puede crear orden para cotizaciones confirmadas'], 422);
        }

        $estatusPendiente = Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);
        $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);

        if (isset($data['estatus']) && !in_array($data['estatus'], [$estatusPendiente->id, $estatusPagado->id], true)) {
            return response()->json(['message' => 'La orden solo admite estatus pendiente de pago o pagado'], 422);
        }

        $data['estatus'] = $data['estatus'] ?? $estatusPendiente->id;
        $data['monto_total'] = 0;

        $item = OrdenCompra::create($data);
        $item->recalcularMontoTotal();

        return response()->json($item->fresh(), 201);
    }

    /**
     * Obtener una orden de compra específica
     *
     * Devuelve los detalles de una orden de compra, incluyendo servicios, tasa de cambio y pagos asociados.
     */
    public function show(OrdenCompra $ordenCompra)
    {
        $ordenCompra->recalcularMontoTotal();

        $ordenCompra->load([
            'cotizacion.servicios',
            'cotizacion.tasaCambio',
            'pagos',
            'estadoFinanciero',
        ]);

        $data = $ordenCompra->toArray();
        $data['servicios'] = $ordenCompra->cotizacion?->servicios ?? [];

        // Calcular el saldo pendiente
        $montoPagado = collect($ordenCompra->pagos)->sum(function ($pago) {
            // Si existe el campo monto_pagado, usarlo; si no, usar monto_asignado
            return isset($pago['monto_pagado']) ? $pago['monto_pagado'] : ($pago['monto_asignado'] ?? 0);
        });
        $data['saldo_pendiente'] = max(0, $ordenCompra->monto_total - $montoPagado);

        return response()->json($data);
    }

    /**
     * Actualizar una orden de compra existente
     *
     * Modifica la tasa de cambio o el estatus de una orden de compra y recalcula su monto total.
     *
     * @bodyParam estatus int ID del estatus (pendiente de pago / pagado). Ejemplo: 1
     */
    public function update(Request $request, OrdenCompra $ordenCompra)
    {
        $data = $request->validate([
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
        ]);

        if (isset($data['estatus'])) {
            $estatusPendiente = Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);
            $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);

            if (!in_array($data['estatus'], [$estatusPendiente->id, $estatusPagado->id], true)) {
                return response()->json(['message' => 'La orden solo admite estatus pendiente de pago o pagado'], 422);
            }
        }

        $estatusAnterior = $ordenCompra->estatus;
        $ordenCompra->update($data);
        $ordenCompra->recalcularMontoTotal();

        // Si cambió el estatus, registrar en historial
        if (isset($data['estatus']) && $data['estatus'] != $estatusAnterior) {
            \App\Models\OrdenCompraHistorial::create([
                'orden_compra_id' => $ordenCompra->id,
                'estatus_anterior' => $estatusAnterior,
                'estatus_nuevo' => $data['estatus'],
                'usuario_id' => auth()->id(),
                'comentario' => 'Cambio de estatus desde API',
            ]);
        }

        return response()->json($ordenCompra->fresh());
    }

    /**
     * Eliminar una orden de compra
     *
     * Elimina permanentemente la orden de compra del sistema.
     */
    public function destroy(OrdenCompra $ordenCompra)
    {
        $ordenCompra->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
