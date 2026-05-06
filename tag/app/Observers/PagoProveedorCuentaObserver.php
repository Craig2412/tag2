<?php

namespace App\Observers;

use App\Models\PagoProveedorCuenta;
use App\Services\EstadoFaseService;

class PagoProveedorCuentaObserver
{
    /**
     * Al registrar una asignación de pago a una cuenta por pagar.
     */
    public function created(PagoProveedorCuenta $pagoCuenta): void
    {
        $cuenta = $pagoCuenta->cuentaPorPagar;
        if ($cuenta) {
            // 1. Restar el monto asignado del saldo pendiente
            $cuenta->saldo_pendiente -= $pagoCuenta->monto_asignado;
            
            // 2. Si el saldo es <= 0, actualizamos el estatus de la cuenta a "Liquidado" (puedes ajustar el ID según tu catálogo)
            // Por ahora solo aseguramos que el saldo baje.
            $cuenta->save();

            // 3. Sincronizar el estado de egreso de la Orden de Compra
            if ($cuenta->ordenCompra) {
                EstadoFaseService::sincronizarEstadoEgreso($cuenta->ordenCompra);
            }
        }
    }

    /**
     * Al eliminar una asignación (por si te equivocas y quieres revertir).
     */
    public function deleted(PagoProveedorCuenta $pagoCuenta): void
    {
        $cuenta = $pagoCuenta->cuentaPorPagar;
        if ($cuenta) {
            // Revertimos el saldo
            $cuenta->saldo_pendiente += $pagoCuenta->monto_asignado;
            $cuenta->save();

            if ($cuenta->ordenCompra) {
                EstadoFaseService::sincronizarEstadoEgreso($cuenta->ordenCompra);
            }
        }
    }
}
