<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\EstadoCotizacion;
use App\Models\OrdenCompra;
use Illuminate\Database\Seeder;

class OrdenesComprasSeeder extends Seeder
{
    public function run(): void
    {
        $cotizacion = Cotizacion::orderBy('id')->first();

        if (!$cotizacion) {
            return;
        }

        $estatusConfirmado = EstadoCotizacion::firstOrCreate(['slug' => 'aprobada'], ['nombre' => 'Aprobada', 'color' => '#10B981']);
        $estatusPendiente = \App\Models\Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);

        $cotizacion->update(['id_estado_cotizacion' => $estatusConfirmado->id]);

        OrdenCompra::firstOrCreate(
            ['id_cotizacion' => $cotizacion->id],
            [
                'estatus' => $estatusPendiente->id,
                'monto_total' => 0,
            ]
        );

        OrdenCompra::where('id_cotizacion', $cotizacion->id)->first()?->recalcularMontoTotal();
    }
}
