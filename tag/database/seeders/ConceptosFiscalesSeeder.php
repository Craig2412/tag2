<?php

namespace Database\Seeders;

use App\Models\ConceptoFiscal;
use Illuminate\Database\Seeder;

/**
 * Crea los conceptos fiscales (impuestos y retenciones) configurables.
 *
 * Todas las tasas son editables desde la base de datos, así como:
 * - sobre qué base se calculan (base gravable o valor de IVA),
 * - a quién se aplican (cliente o empresa),
 * - y la exclusión por palabra clave (p. ej. "boleto").
 */
class ConceptosFiscalesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // ── RETENCIONES QUE APLICA EL CLIENTE (desglose de factura) ──
            [
                'codigo' => 'islr_cliente',
                'nombre' => 'ISLR',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'cliente',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 2.0,
                'excluir_si_contiene' => 'boleto',
                'orden' => 1,
            ],
            [
                'codigo' => 'retencion_iva_cliente',
                'nombre' => 'Retención IVA',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'cliente',
                'base_calculo' => 'valor_iva',
                'porcentaje' => 75.0,
                'excluir_si_contiene' => null,
                'orden' => 2,
            ],
            [
                'codigo' => 'unoxmil_cliente',
                'nombre' => '1 x 1000',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'cliente',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 0.1,
                'excluir_si_contiene' => null,
                'orden' => 3,
            ],
            [
                'codigo' => 'aporte_social_cliente',
                'nombre' => 'Aporte Social',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'cliente',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 3.0,
                'excluir_si_contiene' => null,
                'orden' => 4,
            ],
            [
                'codigo' => 'fuvidit_cliente',
                'nombre' => 'FUVIDIT',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'cliente',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 0.5,
                'excluir_si_contiene' => null,
                'orden' => 5,
            ],
            [
                'codigo' => 'alcaldia_cliente',
                'nombre' => 'Alcaldía',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'cliente',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 1.25,
                'excluir_si_contiene' => null,
                'orden' => 6,
            ],

            // ── RETENCIONES QUE APLICA LA EMPRESA (sobre la OC) ──
            [
                'codigo' => 'alcaldia_empresa',
                'nombre' => 'Alcaldía',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'empresa',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 2.2,
                'excluir_si_contiene' => null,
                'orden' => 1,
            ],
            [
                'codigo' => 'islr_empresa',
                'nombre' => 'ISLR',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'empresa',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 1.0,
                'excluir_si_contiene' => null,
                'orden' => 2,
            ],
            [
                'codigo' => 'inatur_empresa',
                'nombre' => 'INATUR',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'empresa',
                'base_calculo' => 'base_gravable',
                'porcentaje' => 1.0,
                'excluir_si_contiene' => null,
                'orden' => 3,
            ],
            [
                'codigo' => 'retencion_iva_empresa',
                'nombre' => 'IVA',
                'tipo_aplicacion' => 'retencion',
                'aplica_a' => 'empresa',
                'base_calculo' => 'valor_iva',
                'porcentaje' => 25.0,
                'excluir_si_contiene' => null,
                'orden' => 4,
            ],
        ];

        foreach ($items as $item) {
            ConceptoFiscal::updateOrCreate(
                ['codigo' => $item['codigo']],
                $item
            );
        }
    }
}
