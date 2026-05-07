<?php

namespace Database\Seeders;

use App\Models\EstadoOrdenCompra;
use Illuminate\Database\Seeder;

class EstadoOrdenCompraSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['slug' => 'pendiente',   'nombre' => 'Pendiente',   'color' => '#F59E0B'],
            ['slug' => 'en_proceso',  'nombre' => 'En Proceso',  'color' => '#3B82F6'],
            ['slug' => 'completada',  'nombre' => 'Completada',  'color' => '#10B981'],
            ['slug' => 'anulada',     'nombre' => 'Anulada',     'color' => '#EF4444'],
        ];

        foreach ($estados as $estado) {
            EstadoOrdenCompra::firstOrCreate(
                ['slug'   => $estado['slug']],
                ['nombre' => $estado['nombre'], 'color' => $estado['color']]
            );
        }
    }
}
