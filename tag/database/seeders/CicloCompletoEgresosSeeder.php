<?php

namespace Database\Seeders;

use App\Models\CuentaPorPagar;
use App\Models\EstadoFinanciero;
use App\Models\OrdenCompra;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CicloCompletoEgresosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Obtener datos base
            $orden = OrdenCompra::first() ?? OrdenCompra::factory()->create();
            $proveedor = Proveedor::first() ?? Proveedor::create(['nombre' => 'Proveedor de Prueba']);
            $estadoPendiente = EstadoFinanciero::where('slug', 'pendiente')->first() ?? EstadoFinanciero::first();

            // 2. Crear una Cuenta por Pagar (Deuda) de $500
            $cxp = CuentaPorPagar::create([
                'id_orden_compra' => $orden->id,
                'id_proveedor' => $proveedor->id,
                'monto_total' => 500.00,
                'saldo_pendiente' => 500.00,
                'id_estado_financiero' => $estadoPendiente->id,
            ]);

            dump(">>> CxP Creada: #{$cxp->id} con saldo de $500.00");
            dump('>>> Estado Egreso OC inicial: '.($orden->fresh()->id_estado_financiero_egreso ?? 'N/A'));

            // 3. Crear un Pago al Proveedor de $1,000 (Pago global)
            $pago = PagoProveedor::create([
                'id_proveedor' => $proveedor->id,
                'monto_total' => 1000.00,
                'referencia' => 'REF-'.time(),
                'fecha_pago' => now(),
                'id_metodo_pago' => 1,
            ]);

            dump(">>> Pago Creado: #{$pago->id} por $1000.00");

            // 4. ASIGNAR EL PAGO A LA DEUDA (Aquí se dispara el Observer)
            PagoProveedorCuenta::create([
                'id_pago_proveedor' => $pago->id,
                'id_cuenta_por_pagar' => $cxp->id,
                'monto_asignado' => 500.00,
            ]);

            // 5. Verificar resultados
            $cxp->refresh();
            $orden->refresh();

            dump('>>> RESULTADO:');
            dump(">>> Nuevo Saldo Pendiente CxP: {$cxp->saldo_pendiente}");
            dump('>>> Nuevo Estado Egreso OC: '.($orden->id_estado_financiero_egreso ?? 'N/A'));

            if ($cxp->saldo_pendiente == 0) {
                dump('✅ ÉXITO: El ciclo se completó y la deuda se saldó automáticamente.');
            } else {
                dump('❌ ERROR: El saldo no bajó a cero.');
            }
        });
    }
}
