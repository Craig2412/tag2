<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\Estatus;
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
        $cotizacion = Cotizacion::first();
        $estatusActivo = Estatus::firstOrCreate(['estatus' => 'activo']);

        if (!$tipoServicio || !$tasaCambio || !$cotizacion) {
            return;
        }

        $items = [
            ['costo' => 100, 'monto_gravable' => 100, 'monto_no_sujeto' => 0],
            ['costo' => 160, 'monto_gravable' => 140, 'monto_no_sujeto' => 20],
        ];

        foreach ($items as $item) {
            Servicio::firstOrCreate(
                [
                    'id_cotizacion' => $cotizacion->id,
                    'id_tipo_servicio' => $tipoServicio->id,
                    'id_proveedor' => $tipoServicio->id_proveedor,
                    'id_tasa_cambio' => $tasaCambio->id,
                    'costo' => $item['costo'],
                ],
                [
                    'monto_gravable' => $item['monto_gravable'],
                    'monto_no_sujeto' => $item['monto_no_sujeto'],
                    'total_servicio' => $item['monto_gravable'] + $item['monto_no_sujeto'],
                    'estatus' => $estatusActivo->id,
                ]
            );
        }
    }
}
