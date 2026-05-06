<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use App\Models\Servicio;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use App\Models\CuentaPorPagar;
use App\Events\CotizacionEstatusActualizado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class TestCommercialFlow extends Command
{
    protected $signature = 'app:test-conversion';
    protected $description = 'Prueba el flujo completo y PERSISTE los datos en la BD';

    public function handle()
    {
        $this->info("🚀 Iniciando prueba de flujo comercial PERSISTENTE...");

        // Forzamos que los eventos se ejecuten al instante para la prueba
        Config::set('queue.default', 'sync');

        // 1. Buscar estatus Aprobado
        $estatusAprobado = Estatus::where('estatus', 'like', '%aprob%')->first();
        if (!$estatusAprobado) {
            $this->error("No se encontró un estatus que contenga 'aprob' en la base de datos.");
            return;
        }

        // 2. Crear Cotización
        $cotizacion = Cotizacion::create([
            'id_atencion' => 1,
            'id_tipo_cotizacion' => 1,
            'referencia' => 'TEST-' . time(),
            'monto_total' => 0,
            'estatus' => 1,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);
        $this->line("✅ Cotización #{$cotizacion->id} creada.");

        // 3. Agregar Servicios
        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => 1,
            'costo' => 80.00,
            'monto_gravable' => 100.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 100.00,
            'id_tasa_cambio' => 1,
        ]);
        
        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => 1,
            'costo' => 150.00,
            'monto_gravable' => 200.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 200.00,
            'id_tasa_cambio' => 1,
        ]);
        $this->line("✅ 2 Servicios agregados (Venta: $300 / Costo: $230).");

        // 4. DISPARAR LA APROBACIÓN
        $this->info("⌛ Cambiando estatus a '{$estatusAprobado->estatus}'...");
        $cotizacion->estatus = $estatusAprobado->id;
        $cotizacion->save();

        // Disparar el evento
        event(new CotizacionEstatusActualizado($cotizacion, 1, $estatusAprobado->id));

        // 5. VERIFICACIONES
        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        
        if ($orden) {
            $this->info("🎊 ¡ÉXITO! Orden de Compra generada automáticamente.");
            $this->line("   - ID Orden: #{$orden->id}");
            $this->line("   - Monto Total OC (CxC): \${$orden->monto_total} (Esperado: $300)");
            
            $cxp = CuentaPorPagar::where('id_orden_compra', $orden->id)->sum('monto_total');
            $this->line("   - Total Cuentas por Pagar (CxP): \${$cxp} (Esperado: $230)");

            if ($orden->monto_total == 300 && $cxp == 230) {
                $this->info("⭐ TODO PERFECTO: Los montos coinciden exactamente.");
            } else {
                $this->warning("⚠️ Los montos de CxP no coinciden. Revisa si el Listener de CxP falló.");
            }
        } else {
            $this->error("❌ FALLO: No se generó la Orden de Compra.");
        }

        $this->info("📢 Los datos han sido guardados en la BD. Puedes buscarlos en el sistema.");
    }
}
