<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use App\Models\TasaCambio;
use Illuminate\Database\Seeder;

class OrdenesComprasSeeder extends Seeder
{
    public function run(): void
    {
        $cotizacion = Cotizacion::where('borrado_logico', false)->orderBy('id')->first();
        $tasaCambio = TasaCambio::first();

        if (!$cotizacion || !$tasaCambio) {
            return;
        }

        $estatusConfirmado = Estatus::firstOrCreate(['estatus' => 'confirmado']);
        $estatusPendiente = Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);

        $cotizacion->update(['estatus' => $estatusConfirmado->id]);

        OrdenCompra::firstOrCreate(
            ['id_cotizacion' => $cotizacion->id],
            [
                'id_tasa_cambio' => $tasaCambio->id,
                'estatus' => $estatusPendiente->id,
                'monto_total' => 0,
            ]
        );

        OrdenCompra::where('id_cotizacion', $cotizacion->id)->first()?->recalcularMontoTotal();
    }
}
