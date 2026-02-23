<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\PagoCotizacion;
use App\Models\TasaCambio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PagosSeeder extends Seeder
{
    public function run(): void
    {
        $cotizaciones = Cotizacion::where('borrado_logico', false)
            ->orderBy('id')
            ->get();
        $metodo = MetodoPago::first();
        $tasa = TasaCambio::first();

        if ($cotizaciones->count() < 1 || !$metodo || !$tasa) {
            return;
        }

        $detalles = [];

        foreach ($cotizaciones as $cotizacion) {
            $totalServicios = (float) DB::table('servicios_cotizaciones')
                ->join('servicios', 'servicios_cotizaciones.id_servicio', '=', 'servicios.id')
                ->where('servicios_cotizaciones.id_cotizacion', $cotizacion->id)
                ->sum('servicios.total_servicio');

            if ($totalServicios <= 0) {
                continue;
            }

            $detalles[] = [
                'id_cotizacion' => $cotizacion->id,
                'monto_asignado' => min(50, $totalServicios),
            ];

            if (count($detalles) >= 2) {
                break;
            }
        }

        if (count($detalles) === 0) {
            return;
        }

        $montoTotal = array_sum(array_column($detalles, 'monto_asignado'));

        $estatus = Estatus::firstOrCreate(['estatus' => 'por pagar']);

        $pago = Pago::firstOrCreate(
            [
                'nro_comprobante' => 'COMP-0001',
            ],
            [
                'fecha_pago' => now()->toDateString(),
                'monto_total' => $montoTotal,
                'id_metodo_pago' => $metodo->id,
                'id_tasa_cambio' => $tasa->id,
                'estatus' => $estatus->id,
                'borrado_logico' => false,
            ]
        );

        foreach ($detalles as $detalle) {
            PagoCotizacion::firstOrCreate(
                [
                    'id_pago' => $pago->id,
                    'id_cotizacion' => $detalle['id_cotizacion'],
                ],
                [
                    'monto_asignado' => $detalle['monto_asignado'],
                ]
            );
        }
    }
}
