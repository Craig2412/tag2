<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\TasaCambio;
use App\Models\TipoCotizacion;
use Illuminate\Database\Seeder;

class CotizacionesSeeder extends Seeder
{
    public function run(): void
    {
        $atencion = Atencion::first();
        $tasaCambio = TasaCambio::first();
        $tipo = TipoCotizacion::where('tipo_cotizacion', 'personal')->first();

        if (!$atencion || !$tasaCambio || !$tipo) {
            return;
        }

        $estatus = Estatus::firstOrCreate(['estatus' => 'por confirmar']);

        Cotizacion::firstOrCreate(
            ['id_atencion' => $atencion->id],
            [
                'cant_adultos' => 2,
                'cant_menores' => 1,
                'cant_viejos' => 0,
                'id_tipo_cotizacion' => $tipo->id,
                'id_tasa_cambio' => $tasaCambio->id,
                'fecha_vencimiento' => now()->addDays(15),
                'estatus' => $estatus->id,
            ]
        );
    }
}
