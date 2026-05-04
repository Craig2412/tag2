<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use App\Models\PagoProveedor;
use App\Models\CuentaPorPagar;
use App\Http\Resources\PagoProveedorResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\PagoProveedorCreado;
use App\Events\PagoProveedorEliminado;

class PagoProveedorController extends Controller
{
    /**
     * Listar todos los pagos a proveedores
     */
    public function index()
    {
        return PagoProveedorResource::collection(PagoProveedor::with(['proveedor', 'tasaCambio', 'metodoPago', 'estatus_pago', 'cuentasPorPagar'])->orderBy('id', 'desc')->get());
    }

    /**
     * Registrar un pago a proveedor y asociarlo a cuentas por pagar
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'monto_total' => ['required', 'numeric', 'min:0.01'],
            'id_tasa_cambio' => ['nullable', 'exists:tasas_cambio,id'],
            'referencia' => ['required', 'string', 'max:255'],
            'fecha_pago' => ['required', 'date'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id'],
            'estatus' => ['required', 'exists:estatus,id'],
            'comprobante' => ['nullable', 'string'],
            
            // Relación con cuentas por pagar
            'cuentas' => ['nullable', 'array'],
            'cuentas.*.id_cuenta_por_pagar' => ['required', 'exists:cuentas_por_pagar,id'],
            'cuentas.*.monto_asignado' => ['required', 'numeric', 'min:0.01'],
        ]);

        // Verificar que el monto total coincida con la suma de montos asignados si se enviaron cuentas
        if (!empty($data['cuentas'])) {
            $sumaAsignada = collect($data['cuentas'])->sum('monto_asignado');
            // Validar con una pequeña tolerancia por redondeo si es necesario
            if (abs($sumaAsignada - $data['monto_total']) > 0.01) {
                return response()->json([
                    'message' => 'La suma de los montos asignados a las cuentas no coincide con el monto total del pago.'
                ], 422);
            }
        }

        try {
            $pago = DB::transaction(function () use ($data) {
                // 1. Crear el pago
                $pago = PagoProveedor::create([
                    'id_proveedor'   => $data['id_proveedor'],
                    'monto_total'    => $data['monto_total'],
                    'id_tasa_cambio' => $data['id_tasa_cambio'] ?? null,
                    'referencia'     => $data['referencia'],
                    'fecha_pago'     => $data['fecha_pago'],
                    'id_metodo_pago' => $data['id_metodo_pago'],
                    'estatus'        => $data['estatus'],
                    'comprobante'    => $data['comprobante'] ?? null,
                ]);

                // 2. Asociar a cuentas por pagar si se proporcionaron
                if (!empty($data['cuentas'])) {
                    $syncData = [];
                    foreach ($data['cuentas'] as $cuenta) {
                        $syncData[$cuenta['id_cuenta_por_pagar']] = ['monto_asignado' => $cuenta['monto_asignado']];
                    }
                    $pago->cuentasPorPagar()->sync($syncData);
                }

                // 3. Disparar el evento de creación (los listeners se encargan de amortizar y sincronizar)
                event(new PagoProveedorCreado($pago));

                return $pago;
            });

            $pago->load(['proveedor', 'cuentasPorPagar']);
            return new PagoProveedorResource($pago);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar el pago: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener un pago a proveedor específico
     */
    public function show(PagoProveedor $pagoProveedor)
    {
        $pagoProveedor->load(['proveedor', 'tasaCambio', 'metodoPago', 'estatus_pago', 'cuentasPorPagar']);
        return new PagoProveedorResource($pagoProveedor);
    }

    /**
     * Actualizar un pago a proveedor
     */
    public function update(Request $request, PagoProveedor $pagoProveedor)
    {
        $data = $request->validate([
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'monto_total' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_tasa_cambio' => ['sometimes', 'nullable', 'exists:tasas_cambio,id'],
            'referencia' => ['sometimes', 'required', 'string', 'max:255'],
            'fecha_pago' => ['sometimes', 'required', 'date'],
            'id_metodo_pago' => ['sometimes', 'required', 'exists:metodos_pago,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'comprobante' => ['sometimes', 'nullable', 'string'],
        ]);

        $pagoProveedor->update($data);
        $pagoProveedor->load(['proveedor', 'tasaCambio', 'metodoPago', 'estatus_pago', 'cuentasPorPagar']);

        return new PagoProveedorResource($pagoProveedor);
    }

    /**
     * Eliminar un pago a proveedor
     */
    public function destroy(PagoProveedor $pagoProveedor)
    {
        try {
            DB::transaction(function () use ($pagoProveedor) {
                // Disparamos el evento ANTES de eliminar el pago para que el listener pueda leer las relaciones
                event(new PagoProveedorEliminado($pagoProveedor));

                // Eliminar relaciones pivot y el pago
                $pagoProveedor->cuentasPorPagar()->detach();
                $pagoProveedor->delete();
            });

            return response()->json(['data' => ['message' => 'Eliminado correctamente y saldos restaurados']]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }
}
