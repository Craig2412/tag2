<?php

namespace App\Http\Controllers;

use App\Models\PagoOrdenCompra;
use Illuminate\Http\Request;

class PagoOrdenCompraController extends Controller
{
    // Listar todas las relaciones pago-orden-compra
    public function index()
    {
        return response()->json(PagoOrdenCompra::all());
    }

    // Ver una relación específica
    public function show(PagoOrdenCompra $pagoOrdenCompra)
    {
        return response()->json($pagoOrdenCompra);
    }

    // Crear una nueva relación
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_pago' => 'required|exists:pagos,id',
            'id_orden_compra' => 'required|exists:ordenes_compra,id',
            'monto_asignado' => 'required|numeric|min:0',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);
        $relacion = PagoOrdenCompra::create($data);
        return response()->json($relacion, 201);
    }

    // Actualizar una relación
    public function update(Request $request, PagoOrdenCompra $pagoOrdenCompra)
    {
        $data = $request->validate([
            'monto_asignado' => 'sometimes|numeric|min:0',
            'monto_pagado' => 'nullable|numeric|min:0',
        ]);
        $pagoOrdenCompra->update($data);
        return response()->json($pagoOrdenCompra);
    }

    // Eliminar una relación
    public function destroy(PagoOrdenCompra $pagoOrdenCompra)
    {
        $pagoOrdenCompra->delete();
        return response()->json(['message' => 'Eliminado']);
    }
}
