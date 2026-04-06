<?php

namespace Database\Seeders;

use App\Models\User;
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
        $users = User::role('cliente')->get();
        $estatus = Estatus::firstOrCreate(['estatus' => 'activo']);
        $tipoContribuyente = TipoContribuyente::first();

        if ($users->isEmpty()) {
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

        foreach ($users as $user) {
            Cliente::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nombre' => $user->name,
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
