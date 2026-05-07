<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use App\Models\Servicio;
use App\Models\EstadoCotizacion;
use App\Models\OrdenCompra;
use App\Models\CuentaPorPagar;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Models\Atencion;
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
        $this->comment("\n--- FASE 0: ATENCION ---");
        $atencion = Atencion::create([
            'id_cliente' => 1,
            'id_personal' => 1,
            'id_origen_atencion' => 1,
            'asunto' => 'Atencion de Prueba Comando',
            'id_estado_atencion' => 1,
        ]);
        $this->line("✅ Atencion #{$atencion->id} creada.");

        $this->comment("\n--- FASE 1: VENTAS ---");
        $estatusAprobado = EstadoCotizacion::where('slug', 'aprobada')->first();
        $estatusPendiente = EstadoCotizacion::where('slug', 'pendiente')->first();
        
        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'referencia' => 'MASTER-' . time(),
            'monto_total' => 0,
            'id_estado_cotizacion' => $estatusPendiente->id,
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
        
        $this->line("✅ Cotización #{$cotizacion->id} creada en BD con estado: " . $estatusPendiente->nombre);
        
        // Aprobamos en BD y luego disparamos el evento (como lo hace el Controller)
        $this->info("⌛ Aprobando la Cotización y disparando eventos...");
        $cotizacion->update(['id_estado_cotizacion' => $estatusAprobado->id]);
        
        event(new CotizacionEstatusActualizado($cotizacion, $estatusPendiente->id, $estatusAprobado->id));
        
        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        if (!$orden) {
            $this->error("❌ Error: No se generó la Orden de Compra automáticamente tras aprobar la Cotización."); return;
        }
        $this->line("✅ Orden de Compra #{$orden->id} generada en BD respondiendo al evento de Cotización Aprobada.");

        // --- FASE 2: VERIFICAR DEUDA ---
        $this->comment("\n--- FASE 2: EGRESOS (DEUDA) ---");
        $cxp = CuentaPorPagar::where('id_orden_compra', $orden->id)->first();
        if (!$cxp) {
            $this->error("❌ Error: No se generó la Cuenta por Pagar tras crearse la Orden."); return;
        }
        $this->line("✅ Deuda con proveedor #1 creada por \${$cxp->monto_total} (Listener: GenerarCuentasPorPagarListener).");
        $this->line("   Estatus financiero egreso OC en BD: " . $orden->fresh()->id_estado_financiero_egreso . " (Pendiente)");

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

        $this->info("⌛ Asignando pago a la deuda del proveedor...");
        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pago->id,
            'id_cuenta_por_pagar' => $cxp->id,
            'monto_asignado' => 500.00,
        ]);
        $this->line("✅ Observer (PagoProveedorCuentaObserver) redujo el saldo y liquidó la deuda.");

        // --- RESULTADO FINAL ---
        $this->comment("\n--- RESULTADO FINAL ---");
        $ordenFinal = $orden->fresh();
        $cxpFinal = $cxp->fresh();

        $this->line("💰 Saldo pendiente CxP: \${$cxpFinal->saldo_pendiente}");
        $this->line("📊 Estatus Financiero CxP: " . $cxpFinal->id_estado_financiero);
        $this->line("📊 Estatus Egreso OC: " . $ordenFinal->id_estado_financiero_egreso);

        // --- FASE 4: INGRESOS (COBRO AL CLIENTE) ---
        $this->comment("\n--- FASE 4: INGRESOS (COBRO AL CLIENTE) ---");
        
        $pagoCliente = Pago::create([
            'fecha_pago' => now(),
            'monto_total' => 600.00,
            'id_metodo_pago' => 1,
            'nro_comprobante' => 'IN-MASTER-' . time(),
            'id_tasa_cambio' => 1,
            'id_entidad_bancaria' => 1,
            'id_estado_conciliacion' => 1, // Por conciliar
        ]);
        $this->line("✅ Pago de cliente #{$pagoCliente->id} registrado por $600.");

        $this->info("⌛ Asignando cobro a la factura...");
        PagoOrdenCompra::create([
            'id_pago' => $pagoCliente->id,
            'id_orden_compra' => $ordenFinal->id,
            'monto_asignado' => 600.00,
        ]);
        $this->line("✅ Observer (PagoOrdenCompraObserver) redujo la cuenta por cobrar.");

        $ordenIngresoFinal = $ordenFinal->fresh();
        $this->line("📊 Estatus Financiero Ingreso OC Final: " . $ordenIngresoFinal->id_estado_financiero);

        // --- VALIDACIONES FINALES ---
        if ($cxpFinal->saldo_pendiente == 0 && $ordenFinal->id_estado_financiero_egreso == 3 && $ordenIngresoFinal->id_estado_financiero == 3) {
            $this->info("\n🏆 ¡CICLO MAESTRO COMPLETADO PERFECTAMENTE! Todo se automatizó (Ventas -> Egresos -> Ingresos).");
        } else {
            $this->error("⚠️ El ciclo falló. Estatus Egreso (3) => {$ordenFinal->id_estado_financiero_egreso} | Estatus Ingreso (3) => {$ordenIngresoFinal->id_estado_financiero}");
        }

        $this->info("\n📢 Datos persistidos para verificación humana:");
        $this->info("   - Cotización #{$cotizacion->fresh()->id} (Estado: " . $cotizacion->fresh()->estadoCotizacion->nombre . ")");
        $this->info("   - Orden de Compra #{$orden->id}");
        $this->info("   - Pago al Proveedor #{$pago->id}");
        $this->info("   - Pago del Cliente #{$pagoCliente->id}");
    }
}
