<?php

namespace App\Http\Controllers;

use App\Http\Resources\CuentaPorPagarResource;
use App\Models\CuentaPorPagar;
use App\Models\PagoProveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CuentaPorPagarController extends Controller
{
    /**
     * Listar cuentas por pagar
     *
     * Devuelve todas las cuentas por pagar registradas.
     */
    public function index(Request $request)
    {
        $request->validate([
            'origen' => ['nullable', Rule::in([
                CuentaPorPagar::ORIGEN_COMPRA,
                CuentaPorPagar::ORIGEN_COMISION,
                CuentaPorPagar::ORIGEN_MANUAL,
            ])],
        ]);

        $query = CuentaPorPagar::with(['proveedor', 'ordenCompra', 'estadoFinanciero', 'comision']);

        if ($request->filled('origen')) {
            $query->where('origen', $request->origen);
        }
        if ($request->filled('id_cotizacion')) {
            $query->where('id_cotizacion', $request->id_cotizacion);
        }
        if ($request->filled('id_proveedor')) {
            $query->where('id_proveedor', $request->id_proveedor);
        }

        return CuentaPorPagarResource::collection($query->orderBy('id', 'desc')->get());
    }

    /**
     * Registrar una cuenta por pagar manualmente
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_orden_compra' => ['nullable', 'exists:ordenes_compra,id'],
            'origen' => ['nullable', Rule::in([CuentaPorPagar::ORIGEN_COMPRA, CuentaPorPagar::ORIGEN_MANUAL])],
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

        // Manual registrations never create commission payables; those are
        // created by ComisionService during quote approval.
        $data['origen'] = $data['origen']
            ?? (! empty($data['id_orden_compra']) ? CuentaPorPagar::ORIGEN_COMPRA : CuentaPorPagar::ORIGEN_MANUAL);

        $pagoData = $data['pago'] ?? null;
        unset($data['pago']);

        $cuenta = DB::transaction(function () use ($data, $pagoData) {
            $cuenta = CuentaPorPagar::create($data);

            if ($pagoData) {
                $this->crearPagoYAsociar($cuenta, $pagoData);
            }

            return $cuenta;
        });

        $cuenta->load(['proveedor', 'ordenCompra', 'estadoFinanciero', 'pagos', 'comision']);

        return new CuentaPorPagarResource($cuenta);
    }

    /**
     * Ver detalles de una cuenta por pagar
     */
    public function show(CuentaPorPagar $cuentaPorPagar)
    {
        $cuentaPorPagar->load(['proveedor', 'ordenCompra', 'estadoFinanciero', 'pagos', 'comision']);

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

        // Commission payables are managed by the quote: only payments are allowed here.
        if ($cuentaPorPagar->origen === CuentaPorPagar::ORIGEN_COMISION) {
            foreach (['id_orden_compra', 'id_proveedor', 'monto_total', 'saldo_pendiente', 'id_estado_financiero'] as $campo) {
                if (array_key_exists($campo, $data)) {
                    abort(422, 'Las cuentas por pagar de comisión solo admiten pagos. Ajusta la comisión desde la cotización.');
                }
            }
        }

        $pagoData = $data['pago'] ?? null;
        unset($data['pago']);

        DB::transaction(function () use ($cuentaPorPagar, $data, $pagoData) {
            $cuentaPorPagar->update($data);

            if ($pagoData) {
                $this->crearPagoYAsociar($cuentaPorPagar, $pagoData);
            }
        });

        $cuentaPorPagar->load(['proveedor', 'ordenCompra', 'estadoFinanciero', 'pagos', 'comision']);

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
        if ($cuentaPorPagar->origen === CuentaPorPagar::ORIGEN_COMISION) {
            abort(422, 'Las cuentas por pagar de comisión se anulan desde la cotización.');
        }

        $cuentaPorPagar->delete();

        return response()->json(['data' => ['message' => 'Cuenta por pagar eliminada correctamente']]);
    }
}
