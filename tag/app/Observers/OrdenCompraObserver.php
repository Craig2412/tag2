<?php

namespace App\Observers;

use App\Events\AtencionEstatusActualizado;
use App\Events\AtencionEtapaCambiada;
use App\Events\OrdenCompraGuardado;
use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\EstadoCotizacion;
use App\Models\OrdenCompra;
use App\Models\PagoProveedorCuenta;
use App\Services\AtencionStateService;
use App\Services\OrdenStateService;

class OrdenCompraObserver
{
    /**
     * Emite el evento genérico de guardado para que los listeners
     * (SincronizarPadre, SincronizarEstadoFinanciero) reaccionen.
     */
    public function saved(OrdenCompra $ordenCompra): void
    {
        event(new OrdenCompraGuardado($ordenCompra));
    }

    /**
     * Cuando se elimina una OC, se limpian sus cuentas por pagar,
     * los pivotes de pago a proveedores, y la cotización origen se rechaza.
     */
    public function deleted(OrdenCompra $ordenCompra): void
    {
        // 1. Limpiar cuentas por pagar y sus pivotes de pago a proveedores
        $cuentas = $ordenCompra->cuentasPorPagar()->get();

        foreach ($cuentas as $cuenta) {
            // Revertir el saldo pendiente con los pagos ya asignados
            $totalAsignado = PagoProveedorCuenta::where('id_cuenta_por_pagar', $cuenta->id)
                ->sum('monto_asignado');

            if ($totalAsignado > 0) {
                $cuenta->saldo_pendiente += $totalAsignado;
                $cuenta->save();
            }

            // Borrar los pivotes (hard delete vía query builder, sin disparar observers)
            PagoProveedorCuenta::where('id_cuenta_por_pagar', $cuenta->id)->delete();

            // Soft-delete de la cuenta por pagar
            $cuenta->delete();
        }

        // Si es borrado en cascada (cotización padre ya en trash), no ejecutar pasos 2-4
        $cotizacion = Cotizacion::withTrashed()->find($ordenCompra->id_cotizacion);
        if (!$cotizacion || $cotizacion->trashed()) {
            return;
        }

        // 2. Sincronizar el estado de egreso de la OC (quedará como pendiente sin CxP)
        OrdenStateService::sincronizarEgreso($ordenCompra);

        // 3. Marcar la cotización origen como rechazada
        $idRechazada = EstadoCotizacion::where('slug', 'rechazada')->value('id');
        if ($idRechazada && (int) $cotizacion->id_estado_cotizacion !== $idRechazada) {
            $cotizacion->update(['id_estado_cotizacion' => $idRechazada]);
        }

        // 4. Sincronizar el padre (atención)
        $this->sincronizarPadre($ordenCompra);
    }

    private function sincronizarPadre(OrdenCompra $ordenCompra): void
    {
        if ($cotizacion = Cotizacion::find($ordenCompra->id_cotizacion)) {
            if ($atencion = Atencion::find($cotizacion->id_atencion)) {
                $result = AtencionStateService::sincronizarFase($atencion);

                // Disparar eventos basados en el DTO retornado por el servicio
                if ($result->etapa->huboCambio) {
                    event(new AtencionEtapaCambiada($atencion, $result->etapa->anterior, $result->etapa->nuevo));
                }
                if ($result->estatus->huboCambio) {
                    event(new AtencionEstatusActualizado($atencion, $result->estatus->anterior, $result->estatus->nuevo, $result->estatus->comentario));
                }
            }
        }
    }
}
