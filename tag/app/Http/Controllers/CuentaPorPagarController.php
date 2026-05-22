<?php

namespace App\Http\Controllers;

use App\Http\Resources\CuentaPorPagarResource;
use App\Models\CuentaPorPagar;
use App\Models\PagoProveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentaPorPagarController extends Controller
{
    /**
     * Listar cuentas por pagar
     *
     * Devuelve todas las cuentas por pagar registradas.
     */
    public function index()
    {
        return CuentaPorPagarResource::collection(CuentaPorPagar::with(['proveedor', 'ordenCompra', 'estadoFinanciero'])->orderBy('id', 'desc')->get());
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
            'id_estado_financiero' => ['required', 'exists:estados_financieros,id'],

            // Pago opcional: se crea un PagoProveedor y se asocia a esta cuenta
            'pago' => ['nullable', 'array'],
            'pago.monto_total' => ['required_with:pago', 'numeric', 'min:0.01'],
            'pago.id_tasa_cambio' => ['nullable', 'exists:tasas_cambio,id'],
            'pago.referencia' => ['required_with:pago', 'string', 'max:255'],
            'pago.fecha_pago' => ['required_with:pago', 'date'],
            'pago.id_metodo_pago' => ['required_with:pago', 'exists:metodos_pago,id'],
            'pago.comprobante' => ['nullable', 'string'],
            'pago.monto_asignado' => ['required_with:pago', 'numeric', 'min:0.01'],
        ]);

        $pagoData = $data['pago'] ?? null;
        unset($data['pago']);

        $cuenta = DB::transaction(function () use ($data, $pagoData) {
            $cuenta = CuentaPorPagar::create($data);

            if ($pagoData) {
                $this->crearPagoYAsociar($cuenta, $pagoData);
            }

            return $cuenta;
        });

        $cuenta->load(['proveedor', 'ordenCompra', 'estadoFinanciero', 'pagos']);

        return new CuentaPorPagarResource($cuenta);
    }

    /**
     * Ver detalles de una cuenta por pagar
     */
    public function show(CuentaPorPagar $cuentaPorPagar)
    {
        $cuentaPorPagar->load(['proveedor', 'ordenCompra', 'estadoFinanciero', 'pagos']);

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
            'id_estado_financiero' => ['sometimes', 'required', 'exists:estados_financieros,id'],

            // Pago opcional: se crea un PagoProveedor y se asocia a esta cuenta
            'pago' => ['nullable', 'array'],
            'pago.monto_total' => ['required_with:pago', 'numeric', 'min:0.01'],
            'pago.id_tasa_cambio' => ['nullable', 'exists:tasas_cambio,id'],
            'pago.referencia' => ['required_with:pago', 'string', 'max:255'],
            'pago.fecha_pago' => ['required_with:pago', 'date'],
            'pago.id_metodo_pago' => ['required_with:pago', 'exists:metodos_pago,id'],
            'pago.comprobante' => ['nullable', 'string'],
            'pago.monto_asignado' => ['required_with:pago', 'numeric', 'min:0.01'],
        ]);

        $pagoData = $data['pago'] ?? null;
        unset($data['pago']);

        DB::transaction(function () use ($cuentaPorPagar, $data, $pagoData) {
            $cuentaPorPagar->update($data);

            if ($pagoData) {
                $this->crearPagoYAsociar($cuentaPorPagar, $pagoData);
            }
        });

        $cuentaPorPagar->load(['proveedor', 'ordenCompra', 'estadoFinanciero', 'pagos']);

        return new CuentaPorPagarResource($cuentaPorPagar);
    }

    /**
     * Crea un PagoProveedor y lo asocia a la CuentaPorPagar vía pivote.
     * El PagoProveedorCuentaObserver se encarga de amortizar el saldo y sincronizar estados.
     */
    private function crearPagoYAsociar(CuentaPorPagar $cuenta, array $pagoData): void
    {
        $montoAsignado = $pagoData['monto_asignado'];
        unset($pagoData['monto_asignado']);

        // El proveedor del pago es el mismo de la cuenta
        $pagoData['id_proveedor'] = $cuenta->id_proveedor;

        $pago = PagoProveedor::create($pagoData);

        // Crear el registro pivote → el observer amortiza el saldo automáticamente
        $cuenta->pagos()->attach($pago->id, ['monto_asignado' => $montoAsignado]);
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
