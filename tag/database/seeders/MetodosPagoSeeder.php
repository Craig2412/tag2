<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodosPagoSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'efectivo',
            'transferencia',
            'tarjeta',
        ];

        foreach ($items as $metodo) {
            MetodoPago::firstOrCreate(['metodo_pago' => $metodo]);
        }
    }
}
