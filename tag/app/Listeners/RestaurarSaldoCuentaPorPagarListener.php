<?php

namespace App\Listeners;

use App\Events\PagoProveedorEliminado;
use App\Models\CuentaPorPagar;
use App\Models\Estatus;
use Illuminate\Support\Facades\Log;

class RestaurarSaldoCuentaPorPagarListener
{
    public function handle(PagoProveedorEliminado $event): void
    {
        $pago = $event->pago;
        
        // Al eliminar, recorremos las relaciones que tenía antes de ser borradas de la pivote
        foreach ($pago->cuentasPorPagar as $cuenta) {
            $montoARestaurar = $cuenta->pivot->monto_asignado;
            
            $cuentaPorPagar = CuentaPorPagar::find($cuenta->id);
            
            if ($cuentaPorPagar) {
                $cuentaPorPagar->saldo_pendiente += $montoARestaurar;

                // Si el saldo vuelve a ser mayor a 0, regresamos el estatus a "Por Pagar"
                if ($cuentaPorPagar->saldo_pendiente > 0) {
                    $estatusPorPagar = Estatus::where('estatus', 'like', '%por pagar%')->first();
                    if ($estatusPorPagar) {
                        $cuentaPorPagar->estatus = $estatusPorPagar->id;
                    }
                }

                $cuentaPorPagar->save();
                
                Log::info("Saldo restaurado: Se eliminó el pago {$pago->id}. Se devolvieron {$montoARestaurar} a la CxP {$cuentaPorPagar->id}.");

                // Sincronizar el estado de egreso de la orden relacionada
                if ($orden = $cuentaPorPagar->ordenCompra) {
                    \App\Services\EstadoFaseService::sincronizarEstadoEgreso($orden);
                }
            }
        }
    }
}
