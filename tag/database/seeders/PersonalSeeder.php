<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\Personal;
use App\Models\Estatus;
use Illuminate\Database\Seeder;

class PersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuarios = Usuario::role('personal')->get();

        foreach ($usuarios as $usuario) {
            Personal::firstOrCreate(
                ['usuario_id' => $usuario->id],
                [
                    'nombre' => $usuario->nombre_usuario,
                    'apellido' => 'Comercial',
                    'cedula' => 'V-' . rand(10000000, 30000000),
                    'telefono' => '+58 412 ' . rand(1000000, 9999999),
                    'correo_institucional' => strtolower($usuario->nombre_usuario) . '@tag.com',
                    'porcentaje_comision' => 5.00,
                ]
            );
        }
    }
}
