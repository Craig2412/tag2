<?php

namespace App\Observers;

use App\Models\PagoProveedorCuenta;
use App\Services\EstadoFaseService;
use App\Models\EstadoFinanciero;

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
            
            // Lógica de estados financieros
            $slugEstado = 'parcial';
            if ($cuenta->saldo_pendiente <= 0) {
                $slugEstado = 'pagado';
            } elseif ($cuenta->saldo_pendiente >= $cuenta->monto_total) {
                $slugEstado = 'pendiente';
            }

            $estado = EstadoFinanciero::where('slug', $slugEstado)->first();
            if ($estado) {
                $cuenta->id_estado_financiero = $estado->id;
            }

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

            // Lógica de estados financieros
            $slugEstado = 'parcial';
            if ($cuenta->saldo_pendiente >= $cuenta->monto_total) {
                $slugEstado = 'pendiente';
            } elseif ($cuenta->saldo_pendiente <= 0) {
                $slugEstado = 'pagado';
            }

            $estado = EstadoFinanciero::where('slug', $slugEstado)->first();
            if ($estado) {
                $cuenta->id_estado_financiero = $estado->id;
            }

            $cuenta->save();

            if ($cuenta->ordenCompra) {
                EstadoFaseService::sincronizarEstadoEgreso($cuenta->ordenCompra);
            }
        }
    }
}
