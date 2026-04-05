<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use Illuminate\Http\Request;

class OrdenCompraController extends Controller
{
    public function index()
    {
        return response()->json(OrdenCompra::with(['cotizacion', 'tasaCambio'])->orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cotizacion' => ['required', 'exists:cotizaciones,id'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
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

    public function show(OrdenCompra $ordenCompra)
    {
        $ordenCompra->recalcularMontoTotal();

        $ordenCompra->load([
            'cotizacion.serviciosCotizaciones.servicio',
            'tasaCambio',
            'pagos',
        ]);

        $data = $ordenCompra->toArray();
        $data['servicios'] = collect($ordenCompra->cotizacion?->serviciosCotizaciones ?? [])
            ->map(fn ($relacion) => $relacion->servicio)
            ->filter()
            ->values()
            ->all();

        // Calcular el saldo pendiente
        $montoPagado = collect($ordenCompra->pagos)->sum(function ($pago) {
            // Si existe el campo monto_pagado, usarlo; si no, usar monto_asignado
            return isset($pago['monto_pagado']) ? $pago['monto_pagado'] : ($pago['monto_asignado'] ?? 0);
        });
        $data['saldo_pendiente'] = max(0, $ordenCompra->monto_total - $montoPagado);

        return response()->json($data);
    }

    public function update(Request $request, OrdenCompra $ordenCompra)
    {
        $data = $request->validate([
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
        ]);

        if (isset($data['estatus'])) {
            $estatusPendiente = Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);
            $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);

            if (!in_array($data['estatus'], [$estatusPendiente->id, $estatusPagado->id], true)) {
                return response()->json(['message' => 'La orden solo admite estatus pendiente de pago o pagado'], 422);
            }
        }

        $ordenCompra->update($data);
        $ordenCompra->recalcularMontoTotal();

        return response()->json($ordenCompra->fresh());
    }

    public function destroy(OrdenCompra $ordenCompra)
    {
        $ordenCompra->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
