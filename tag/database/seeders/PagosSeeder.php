<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\ServicioCotizacion;
use App\Models\TasaCambio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PagosSeeder extends Seeder
{
    public function run(): void
    {
        $cotizacion = Cotizacion::first();
        $metodo = MetodoPago::first();
        $tasa = TasaCambio::first();

        if (!$cotizacion || !$metodo || !$tasa) {
            return;
        }

        $totalServicios = (float) DB::table('servicios_cotizaciones')
            ->join('servicios', 'servicios_cotizaciones.id_servicio', '=', 'servicios.id')
            ->where('servicios_cotizaciones.id_cotizacion', $cotizacion->id)
            ->sum('servicios.total_servicio');

        if ($totalServicios <= 0) {
            return;
        }

        $estatus = Estatus::firstOrCreate(['estatus' => 'pagado']);

        Pago::firstOrCreate(
            [
                'id_cotizacion' => $cotizacion->id,
                'nro_comprobante' => 'COMP-0001',
            ],
            [
                'fecha_pago' => now()->toDateString(),
                'monto_abono' => $totalServicios,
                'id_metodo_pago' => $metodo->id,
                'id_tasa_cambio' => $tasa->id,
                'estatus' => $estatus->id,
                'borrado_logico' => false,
            ]
        );

        $cotizacion->update(['estatus' => $estatus->id]);
    }
}
