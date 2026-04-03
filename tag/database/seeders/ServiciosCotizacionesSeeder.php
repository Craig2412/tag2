<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\Servicio;
use App\Models\ServicioCotizacion;
use Illuminate\Database\Seeder;

class ServiciosCotizacionesSeeder extends Seeder
{
    public function run(): void
    {
        $cotizacion = Cotizacion::first();
        $servicios = Servicio::where('borrado_logico', false)->orderBy('id')->get();

        if (!$cotizacion || $servicios->isEmpty()) {
            return;
        }

        foreach ($servicios as $servicio) {
            ServicioCotizacion::firstOrCreate([
                'id_cotizacion' => $cotizacion->id,
                'id_servicio' => $servicio->id,
            ]);
        }
    }
}
