<?php

namespace Database\Seeders;

use App\Models\User;
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
        $users = User::role('personal')->get();
        $estatus = Estatus::firstOrCreate(['estatus' => 'activo']);

        foreach ($users as $user) {
            Personal::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nombre' => $user->name,
                    'apellido' => 'Comercial',
                    'cedula' => 'V-' . rand(10000000, 30000000),
                    'telefono' => '+58 412 ' . rand(1000000, 9999999),
                    'correo_institucional' => strtolower($user->name) . '@tag.com',
                    'porcentaje_comision' => 5.00,
                    'id_estatus' => $estatus->id,
                ]
            );
        }
    }
}
