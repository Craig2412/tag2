<?php

namespace Database\Seeders;

use App\Models\Estatus;
use Illuminate\Database\Seeder;

class EstatusSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'activo',
            'inactivo',
            'en espera',
            'por confirmar',
            'confirmado',
            'pendiente de pago',
            'por pagar',
            'pagado',
            'en proceso',
            'aprobado',
            'por aprobar',
        ];

        foreach ($items as $estatus) {
            Estatus::firstOrCreate(['estatus' => $estatus]);
        }
    }
}
