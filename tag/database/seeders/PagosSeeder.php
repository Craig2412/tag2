<?php

namespace Database\Seeders;

use App\Models\Estatus;
use App\Models\MetodoPago;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Models\TasaCambio;
use App\Models\EstadoConciliacion;
use Illuminate\Database\Seeder;

class PagosSeeder extends Seeder
{
    public function run(): void
    {
        $ordenesCompra = OrdenCompra::query()
            ->orderBy('id')
            ->get();
        $metodo = MetodoPago::first();
        $tasa = TasaCambio::first();
        $entidades = \App\Models\EntidadBancaria::all();

        if ($ordenesCompra->count() < 1 || !$metodo || !$tasa || $entidades->count() < 1) {
            return;
        }

        $estadoConciliacion = EstadoConciliacion::where('slug', 'por_conciliar')->first();

        $contador = 1;
        foreach ($ordenesCompra as $ordenCompra) {
            $ordenCompra->recalcularMontoTotal();
            $totalServicios = (float) $ordenCompra->monto_total;

            if ($totalServicios <= 0) {
                continue;
            }

            // Asignar entidad bancaria de forma alterna para ejemplo
            $entidadBancaria = $entidades[($contador - 1) % $entidades->count()];

            $nroComprobante = 'COMP-' . str_pad($contador, 4, '0', STR_PAD_LEFT);
            $pago = Pago::firstOrCreate(
                [
                    'nro_comprobante' => $nroComprobante,
                ],
                [
                    'fecha_pago' => now()->toDateString(),
                    'monto_total' => $totalServicios,
                    'id_metodo_pago' => $metodo->id,
                    'id_tasa_cambio' => $tasa->id,
                    'id_entidad_bancaria' => $entidadBancaria->id,
                    'id_estado_conciliacion' => $estadoConciliacion ? $estadoConciliacion->id : 1,
                ]
            );

            PagoOrdenCompra::firstOrCreate(
                [
                    'id_pago' => $pago->id,
                    'id_orden_compra' => $ordenCompra->id,
                ],
                [
                    'monto_asignado' => $totalServicios,
                ]
            );
            $contador++;
        }
    }
}
