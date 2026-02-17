<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\TasaCambio;
use Illuminate\Database\Seeder;

class CotizacionesSeeder extends Seeder
{
    public function run(): void
    {
        $atencion = Atencion::first();
        $tasa = TasaCambio::first();

        if (!$atencion || !$tasa) {
            return;
        }

        $estatus = Estatus::firstOrCreate(['estatus' => 'por pagar']);

        Cotizacion::firstOrCreate(
            ['id_atencion' => $atencion->id],
            [
                'cant_adultos' => 2,
                'cant_menores' => 1,
                'cant_viejos' => 0,
                'id_tasa_cambio' => $tasa->id,
                'estatus' => $estatus->id,
                'borrado_logico' => false,
            ]
        );
    }
}
