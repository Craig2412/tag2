<?php

namespace Database\Seeders;

use App\Models\Servicio;
use App\Models\TasaCambio;
use App\Models\TipoServicio;
use Illuminate\Database\Seeder;

class ServiciosSeeder extends Seeder
{
    public function run(): void
    {
        $tipoServicio = TipoServicio::first();
        $tasaCambio = TasaCambio::first();

        if (!$tipoServicio || !$tasaCambio) {
            return;
        }

        Servicio::firstOrCreate(
            [
                'id_tipo_servicio' => $tipoServicio->id,
                'id_proveedor' => $tipoServicio->id_proveedor,
                'id_tasa_cambio' => $tasaCambio->id,
            ],
            [
                'costo' => 100,
                'monto_gravable' => 100,
                'monto_no_sujeto' => 0,
                'total_servicio' => 100,
                'borrado_logico' => false,
            ]
        );
    }
}
