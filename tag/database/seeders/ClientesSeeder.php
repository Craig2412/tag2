<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Estatus;
use App\Models\TipoContribuyente;
use Illuminate\Database\Seeder;

class ClientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuarios = Usuario::role('cliente')->get();
        $estatus = Estatus::firstOrCreate(['estatus' => 'activo']);
        $tipoContribuyente = TipoContribuyente::first();

        if ($usuarios->isEmpty()) {
            // Asegurar que exista un cliente aunque no haya usuarios con ese rol por ahora.
            Cliente::firstOrCreate(
                ['cedula' => 'V-12345678'],
                [
                    'nombre' => 'Consumidor',
                    'apellido' => 'Final',
                    'telefono' => '+58 212 555-1212',
                    'id_tipo_contribuyente' => $tipoContribuyente ? $tipoContribuyente->id : null,
                    'id_estatus' => $estatus->id,
                ]
            );
        }

        foreach ($usuarios as $usuario) {
            Cliente::firstOrCreate(
                ['usuario_id' => $usuario->id],
                [
                    'nombre' => $usuario->nombre_usuario,
                    'apellido' => 'General',
                    'cedula' => 'V-' . rand(10000000, 30000000),
                    'telefono' => '+58 424 ' . rand(1000000, 9999999),
                    'id_tipo_contribuyente' => $tipoContribuyente ? $tipoContribuyente->id : null,
                    'id_estatus' => $estatus->id,
                ]
            );
        }
    }
}
