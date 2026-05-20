<?php

namespace App\Observers;

use App\Models\CuentaPorPagar;
use App\Models\EstadoFinanciero;
use App\Models\OrdenCompra;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Services\EstadoFaseService;

/**
 * Protege la integridad de los estados financieros cuando se elimina
 * un PagoProveedor (soft-delete o force-delete).
 */
class PagoProveedorObserver
{
    /**
     * Antes de eliminar el pago, revertir saldos de cuentas por pagar,
     * borrar los pivotes y sincronizar el egreso de cada OC afectada.
     */
    public function deleting(PagoProveedor $pagoProveedor): void
    {
        // 1. Recolectar datos de los pivotes antes de borrarlos
        $pivotes = PagoProveedorCuenta::where('id_pago_proveedor', $pagoProveedor->id)->get();

        $ocsAfectadas = [];

        foreach ($pivotes as $pivote) {
            // 2. Revertir saldo de cada CuentaPorPagar
            $cuenta = CuentaPorPagar::find($pivote->id_cuenta_por_pagar);
            if ($cuenta) {
                $cuenta->saldo_pendiente += $pivote->monto_asignado;

                // Recalcular estado financiero de la cuenta
                $slug = 'parcial';
                if ($cuenta->saldo_pendiente >= $cuenta->monto_total) {
                    $slug = 'pendiente';
                } elseif ($cuenta->saldo_pendiente <= 0) {
                    $slug = 'pagado';
                }
                $estado = EstadoFinanciero::where('slug', $slug)->first();
                if ($estado) {
                    $cuenta->id_estado_financiero = $estado->id;
                }
                $cuenta->save();

                // Registrar OC afectada
                if ($cuenta->id_orden_compra) {
                    $ocsAfectadas[$cuenta->id_orden_compra] = true;
                }
            }
        }

        // 3. Borrar los pivotes
        PagoProveedorCuenta::where('id_pago_proveedor', $pagoProveedor->id)->delete();

        // 4. Sincronizar cada OC afectada
        foreach (array_keys($ocsAfectadas) as $ordenId) {
            if ($orden = OrdenCompra::find($ordenId)) {
                EstadoFaseService::sincronizarEstadoEgreso($orden);
            }
        }
    }
}
