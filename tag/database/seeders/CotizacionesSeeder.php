<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\Tasa;
use App\Models\TipoCotizacion;
use Illuminate\Database\Seeder;

class CotizacionesSeeder extends Seeder
{
    public function run(): void
    {
        $atencion = Atencion::first();
        $tasaAsignada = Tasa::first();
        $tipo = TipoCotizacion::where('tipo_cotizacion', 'personal')->first();

        if (!$atencion || !$tasaAsignada || !$tipo) {
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
                'id_tasa_asignada' => $tasaAsignada->id,
                'estatus' => $estatus->id,
                'borrado_logico' => false,
            ]
        );
    }
}
