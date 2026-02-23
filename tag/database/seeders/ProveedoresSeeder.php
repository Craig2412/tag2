<?php

namespace Database\Seeders;

use App\Models\Estatus;
use App\Models\Proveedor;
use App\Models\TipoProveedor;
use Illuminate\Database\Seeder;

class ProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $tipo = TipoProveedor::firstOrCreate(['tipo_proveedor' => 'servicios profesionales']);
        $estatus = Estatus::firstOrCreate(['estatus' => 'activo']);

        Proveedor::firstOrCreate(
            ['rif' => 'J-12345678-9'],
            [
                'nombre_empresa' => 'Servicios Delta',
                'razon_comercial' => 'Delta Servicios',
                'correo_empresa' => 'contacto@serviciosdelta.test',
                'telefono_empresa' => '02120000000',
                'nombre_persona_contacto' => 'Maria Perez',
                'tipo_proveedor' => $tipo->id,
                'estatus' => $estatus->id,
                'borrado_logico' => false,
            ]
        );
    }
}
