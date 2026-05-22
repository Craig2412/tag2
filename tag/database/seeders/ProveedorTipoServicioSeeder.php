<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\TipoServicio;
use Illuminate\Database\Seeder;

class ProveedorTipoServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proveedor = Proveedor::first();
        $tiposServicio = TipoServicio::pluck('id')->toArray();

        if ($proveedor && ! empty($tiposServicio)) {
            // Asigna los primeros 3 tipos de servicio al primer proveedor como ejemplo
            $proveedor->tiposServicio()->sync(array_slice($tiposServicio, 0, 3));
        }
    }
}
