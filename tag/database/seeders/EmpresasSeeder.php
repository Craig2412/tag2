<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\TipoContribuyente;
use Illuminate\Database\Seeder;

class EmpresasSeeder extends Seeder
{
    public function run(): void
    {
        $tipo = TipoContribuyente::where('tipo_contribuyente', 'Normal')->first();

        if (! $tipo) {
            return;
        }

        Empresa::firstOrCreate(
            ['rif' => 'J-00000000-0'],
            [
                'razon_social' => 'Empresa Demo',
                'razon_comercial' => 'Demo C.A.',
                'numero_telefono' => '02120000000',
                'correo_electronico' => 'contacto@empresa-demo.test',
                'direccion' => 'Direccion demo',
                'id_tipo_contribuyente' => $tipo->id,
            ]
        );
    }
}
