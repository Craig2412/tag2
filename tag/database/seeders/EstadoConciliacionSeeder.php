<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadoConciliacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            ['nombre' => 'Por Conciliar', 'slug' => 'por_conciliar', 'color' => 'warning'],
            ['nombre' => 'Conciliado', 'slug' => 'conciliado', 'color' => 'success'],
            ['nombre' => 'Rechazado', 'slug' => 'rechazado', 'color' => 'danger'],
        ];

        foreach ($estados as $estado) {
            \App\Models\EstadoConciliacion::updateOrCreate(
                ['slug' => $estado['slug']],
                $estado
            );
        }
    }
}
