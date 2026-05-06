<?php

namespace App\Listeners;

use App\Events\PagoProveedorCreado;
use App\Models\CuentaPorPagar;
use App\Models\Estatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

class AmortizarCuentaPorPagarListener implements ShouldQueue
{
    public function handle(PagoProveedorCreado $event): void
    {
        $pago = $event->pago;
        
        // Cargamos las cuentas vinculadas a través de la tabla pivote
        $pago->load('cuentasPorPagar');

        foreach ($pago->cuentasPorPagar as $cuenta) {
            $montoAsignado = $cuenta->pivot->monto_asignado;
            
            // Recargamos el modelo para evitar problemas de concurrencia
            $cuentaPorPagar = CuentaPorPagar::find($cuenta->id);
            
            if ($cuentaPorPagar) {
                $nuevoSaldo = max(0, $cuentaPorPagar->saldo_pendiente - $montoAsignado);
                $cuentaPorPagar->saldo_pendiente = $nuevoSaldo;

                // Si el saldo llega a 0, cambiamos el estatus a "Pagado"
                if ($nuevoSaldo <= 0) {
                    $estatusPagado = Estatus::where('estatus', 'like', '%pagad%')->first();
                    if ($estatusPagado) {
                        $cuentaPorPagar->estatus = $estatusPagado->id;
                    }
                }

                $cuentaPorPagar->save();
                
                Log::info("Amortización aplicada: Pago {$pago->id} redujo {$montoAsignado} a la CxP {$cuentaPorPagar->id}. Nuevo saldo: {$nuevoSaldo}");

                // Sincronizar el estado de egreso de la orden relacionada
                if ($orden = $cuentaPorPagar->ordenCompra) {
                    \App\Services\EstadoFaseService::sincronizarEstadoEgreso($orden);
                }
            }
        }
    }
}
