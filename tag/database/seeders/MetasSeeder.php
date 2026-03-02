<?php

namespace Database\Seeders;

use App\Models\Meta;
use App\Models\Temporalidad;
use Illuminate\Database\Seeder;

class MetasSeeder extends Seeder
{
    public function run(): void
    {
        $porTemporalidad = [
            'Diario' => [5, 3, 2],
            'Semanal' => [20, 12, 8],
            'Mensual' => [80, 50, 35],
            'Anual' => [900, 600, 450],
        ];

        foreach ($porTemporalidad as $nombre => $metrica) {
            $temporalidad = Temporalidad::where('temporalidad', $nombre)->first();

            if (!$temporalidad) {
                continue;
            }

            Meta::firstOrCreate(
                ['id_temporalidad' => $temporalidad->id],
                [
                    'cant_atenciones_aprobadas' => $metrica[0],
                    'cant_cotizaciones_cerradas' => $metrica[1],
                    'cant_cotizaciones_pagadas' => $metrica[2],
                ]
            );
        }
    }
}
