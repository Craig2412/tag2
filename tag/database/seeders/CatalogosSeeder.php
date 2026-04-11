<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('etapas_comerciales')->insert([
            ['id' => 1, 'slug' => 'atencion', 'label' => 'En Atención', 'color' => '#3b82f6', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'slug' => 'cotizada', 'label' => 'Cotizada', 'color' => '#f59e0b', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'slug' => 'orden_compra', 'label' => 'Orden de Compra', 'color' => '#10b981', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('estados_financieros')->insert([
            ['id' => 1, 'slug' => 'pendiente', 'label' => 'Pendiente', 'color' => '#ef4444', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'slug' => 'parcial', 'label' => 'Pagado Parcialmente', 'color' => '#f59e0b', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'slug' => 'pagado', 'label' => 'Pagado', 'color' => '#10b981', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
