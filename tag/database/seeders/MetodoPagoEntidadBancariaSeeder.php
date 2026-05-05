<?php

namespace Database\Seeders;

use App\Models\EntidadBancaria;
use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodoPagoEntidadBancariaSeeder extends Seeder
{
    /**
     * Asocia las entidades bancarias con los métodos de pago que las requieren.
     * Los métodos sin registros en esta tabla (ej. Efectivo) son considerados
     * "sin banco" y el campo id_entidad_bancaria en pagos quedará null.
     */
    public function run(): void
    {
        // Métodos que SÍ requieren entidad bancaria
        $metodosConBanco = ['transferencia', 'tarjeta'];

        $entidades = EntidadBancaria::pluck('id')->toArray();

        if (empty($entidades)) {
            return;
        }

        foreach ($metodosConBanco as $nombreMetodo) {
            $metodo = MetodoPago::where('metodo_pago', $nombreMetodo)->first();

            if ($metodo) {
                // Asocia TODAS las entidades bancarias del sistema a este método
                $metodo->entidadesBancarias()->syncWithoutDetaching($entidades);
            }
        }
        // 'efectivo' no tiene ningún registro → el front sabrá que no necesita banco
    }
}
