<?php

namespace Database\Seeders;

use App\Models\EstadoAtencion;
use Illuminate\Database\Seeder;

class EstadoAtencionSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['slug' => 'abierta', 'nombre' => 'Abierta', 'color' => '#10B981'], // Verde esmeralda
            ['slug' => 'cerrada_ganada', 'nombre' => 'Cerrada Ganada', 'color' => '#3B82F6'], // Azul
            ['slug' => 'cerrada_perdida', 'nombre' => 'Cerrada Perdida', 'color' => '#EF4444'], // Rojo
        ];

        foreach ($estados as $estado) {
            EstadoAtencion::firstOrCreate(
                ['slug' => $estado['slug']],
                ['nombre' => $estado['nombre'], 'color' => $estado['color']]
            );
        }
    }
}
