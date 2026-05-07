<?php

namespace Database\Seeders;

use App\Models\Meta;
use App\Models\Temporalidad;
use Illuminate\Database\Seeder;

class MetasSeeder extends Seeder
{
    public function run(): void
    {
        $metasData = [
            [
                'nombre'           => 'Cierre de Atenciones Mensual',
                'tipo_entidad'     => 'atencion',
                'estatus_objetivo' => 'aprobado',
                'es_monetario'     => false,
                'valor_objetivo'   => 50,
                'temporalidad'     => 'Mensual'
            ],
            [
                'nombre'           => 'Recaudación por Ventas Semanal',
                'tipo_entidad'     => 'orden_compra',
                'estatus_objetivo' => 'pagado',
                'es_monetario'     => true,
                'valor_objetivo'   => 2500,
                'temporalidad'     => 'Semanal'
            ]
        ];

        foreach ($metasData as $data) {
            $temporalidad = Temporalidad::where('temporalidad', $data['temporalidad'])->first();
            if ($temporalidad) {
                Meta::firstOrCreate(
                    ['nombre' => $data['nombre']],
                    [
                        'tipo_entidad'     => $data['tipo_entidad'],
                        'estatus_objetivo'  => $data['estatus_objetivo'],
                        'es_monetario'     => $data['es_monetario'],
                        'valor_objetivo'   => $data['valor_objetivo'],
                        'id_temporalidad'  => $temporalidad->id,
                    ]
                );
            }
        }
    }
}
