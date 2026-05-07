<?php

namespace Database\Seeders;

use App\Models\EstadoCotizacion;
use Illuminate\Database\Seeder;

class EstadoCotizacionSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['slug' => 'pendiente', 'nombre' => 'Pendiente', 'color' => '#F59E0B'], // Naranja
            ['slug' => 'aprobada', 'nombre' => 'Aprobada', 'color' => '#10B981'], // Verde esmeralda
            ['slug' => 'rechazada', 'nombre' => 'Rechazada', 'color' => '#EF4444'], // Rojo
        ];

        foreach ($estados as $estado) {
            EstadoCotizacion::firstOrCreate(
                ['slug' => $estado['slug']],
                ['nombre' => $estado['nombre'], 'color' => $estado['color']]
            );
        }
    }
}
