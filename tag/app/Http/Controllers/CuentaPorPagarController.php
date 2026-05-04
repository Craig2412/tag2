<?php

namespace App\Http\Controllers;

use App\Models\CuentaPorPagar;
use App\Http\Resources\CuentaPorPagarResource;
use Illuminate\Http\Request;

class CuentaPorPagarController extends Controller
{
    /**
     * Listar cuentas por pagar
     *
     * Devuelve todas las cuentas por pagar registradas.
     */
    public function index()
    {
        return CuentaPorPagarResource::collection(CuentaPorPagar::with(['proveedor', 'ordenCompra', 'estatusCuenta'])->orderBy('id', 'desc')->get());
    }

    /**
     * Registrar una cuenta por pagar manualmente
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_orden_compra' => ['nullable', 'exists:ordenes_compra,id'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'monto_total' => ['required', 'numeric', 'min:0.01'],
            'saldo_pendiente' => ['required', 'numeric', 'min:0'],
            'estatus' => ['required', 'exists:estatus,id'],
        ]);

        $cuenta = CuentaPorPagar::create($data);
        $cuenta->load(['proveedor', 'ordenCompra', 'estatusCuenta']);

        return new CuentaPorPagarResource($cuenta);
    }

    /**
     * Ver detalles de una cuenta por pagar
     */
    public function show(CuentaPorPagar $cuentaPorPagar)
    {
        $cuentaPorPagar->load(['proveedor', 'ordenCompra', 'estatusCuenta', 'pagos']);
        return new CuentaPorPagarResource($cuentaPorPagar);
    }

    /**
     * Actualizar una cuenta por pagar
     */
    public function update(Request $request, CuentaPorPagar $cuentaPorPagar)
    {
        $data = $request->validate([
            'id_orden_compra' => ['sometimes', 'nullable', 'exists:ordenes_compra,id'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'monto_total' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'saldo_pendiente' => ['sometimes', 'required', 'numeric', 'min:0'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
        ]);

        $cuentaPorPagar->update($data);
        $cuentaPorPagar->load(['proveedor', 'ordenCompra', 'estatusCuenta']);

        return new CuentaPorPagarResource($cuentaPorPagar);
    }

    /**
     * Eliminar una cuenta por pagar
     */
    public function destroy(CuentaPorPagar $cuentaPorPagar)
    {
        $cuentaPorPagar->delete();
        return response()->json(['data' => ['message' => 'Cuenta por pagar eliminada correctamente']]);
    }
}
