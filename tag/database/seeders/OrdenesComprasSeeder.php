<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\EstadoCotizacion;
use App\Models\EstadoFinanciero;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use Illuminate\Database\Seeder;

class OrdenesComprasSeeder extends Seeder
{
    public function run(): void
    {
        $cotizacion = Cotizacion::orderBy('id')->first();

        if (! $cotizacion) {
            return;
        }

        $estatusConfirmado = EstadoCotizacion::firstOrCreate(['slug' => 'aprobada'], ['nombre' => 'Aprobada', 'color' => '#10B981']);
        $estadoPendienteOC = EstadoOrdenCompra::firstOrCreate(['slug' => 'pendiente'], ['nombre' => 'Pendiente', 'color' => '#6B7280']);
        $estadoFinancieroPendiente = EstadoFinanciero::firstOrCreate(['slug' => 'pendiente'], ['label' => 'Pendiente', 'color' => '#6B7280']);

        $cotizacion->update(['id_estado_cotizacion' => $estatusConfirmado->id]);

        OrdenCompra::firstOrCreate(
            ['id_cotizacion' => $cotizacion->id],
            [
                'id_estado_orden_compra' => $estadoPendienteOC->id,
                'id_estado_financiero' => $estadoFinancieroPendiente->id,
                'id_estado_financiero_egreso' => $estadoFinancieroPendiente->id,
                'monto_total' => 0,
            ]
        );

        OrdenCompra::where('id_cotizacion', $cotizacion->id)->first()?->recalcularMontoTotal();
    }
}
