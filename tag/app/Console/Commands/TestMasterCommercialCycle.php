<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use App\Models\Servicio;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use App\Models\CuentaPorPagar;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Events\CotizacionEstatusActualizado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TestMasterCommercialCycle extends Command
{
    protected $signature = 'app:test-master';
    protected $description = 'Prueba el ciclo de vida completo: Cotización -> Orden -> CxP -> Pago -> Liquidación';

    public function handle()
    {
        $this->info("🏗️  Iniciando Simulación del Ciclo Maestro...");
        Config::set('queue.default', 'sync');

        // --- FASE 1: COTIZACIÓN Y CONVERSIÓN ---
        $this->comment("\n--- FASE 1: VENTAS ---");
        $estatusAprobado = Estatus::where('estatus', 'like', '%aprob%')->first();
        
        $cotizacion = Cotizacion::create([
            'id_atencion' => 1,
            'id_tipo_cotizacion' => 1,
            'referencia' => 'MASTER-' . time(),
            'monto_total' => 0,
            'estatus' => 1,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);
        
        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => 1,
            'costo' => 500.00,
            'monto_gravable' => 600.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 600.00,
            'id_tasa_cambio' => 1,
        ]);
        
        $this->line("✅ Cotización #{$cotizacion->id} creada con costo de $500 y venta de $600.");
        
        // Aprobamos
        event(new CotizacionEstatusActualizado($cotizacion, 1, $estatusAprobado->id));
        
        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        if (!$orden) {
            $this->error("❌ Error: No se generó la Orden de Compra."); return;
        }
        $this->line("✅ Orden de Compra #{$orden->id} generada automáticamente.");

        // --- FASE 2: VERIFICAR DEUDA ---
        $this->comment("\n--- FASE 2: EGRESOS (DEUDA) ---");
        $cxp = CuentaPorPagar::where('id_orden_compra', $orden->id)->first();
        if (!$cxp) {
            $this->error("❌ Error: No se generó la Cuenta por Pagar."); return;
        }
        $this->line("✅ Deuda con proveedor creada por \${$cxp->monto_total}.");
        $this->line("   Estatus financiero egreso OC: " . $orden->fresh()->id_estado_financiero_egreso . " (Pendiente)");

        // --- FASE 3: PAGO Y LIQUIDACIÓN ---
        $this->comment("\n--- FASE 3: PAGOS Y CIERRE ---");
        
        $pago = PagoProveedor::create([
            'id_proveedor' => 1,
            'id_metodo_pago' => 1,
            'monto_total' => 500.00,
            'fecha_pago' => now(),
            'referencia' => 'PAGO-MASTER-' . time(),
            'id_tasa_cambio' => 1,
            'id_usuario' => 1,
        ]);
        $this->line("✅ Pago a proveedor #{$pago->id} registrado por $500.");

        $this->info("⌛ Asignando pago a la deuda...");
        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pago->id,
            'id_cuenta_por_pagar' => $cxp->id,
            'monto_asignado' => 500.00,
        ]);

        // --- RESULTADO FINAL ---
        $this->comment("\n--- RESULTADO FINAL ---");
        $ordenFinal = $orden->fresh();
        $cxpFinal = $cxp->fresh();

        $this->line("💰 Saldo pendiente CxP: \${$cxpFinal->saldo_pendiente}");
        $this->line("📊 Estatus Egreso OC: " . $ordenFinal->id_estado_financiero_egreso);

        if ($cxpFinal->saldo_pendiente == 0 && $ordenFinal->id_estado_financiero_egreso == 3) {
            $this->info("🏆 ¡CICLO MAESTRO COMPLETADO! Todo se automatizó correctamente.");
        } else {
            $this->error("⚠️ El ciclo se completó pero el estatus final no es 'Pagado' (3).");
        }

        $this->info("\n📢 Los datos (Cotización #{$cotizacion->id}, Orden #{$orden->id}, Pago #{$pago->id}) están persistidos en la BD para que los veas.");
    }
}
