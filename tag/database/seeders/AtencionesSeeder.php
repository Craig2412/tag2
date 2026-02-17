<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\Estatus;
use App\Models\Origen;
use App\Models\User;
use Illuminate\Database\Seeder;

class AtencionesSeeder extends Seeder
{
    public function run(): void
    {
        $cliente = User::role('cliente')->first();
        $personal = User::role('personal')->first();
        $origen = Origen::first();

        if (!$cliente || !$personal || !$origen) {
            return;
        }

        $estatus = Estatus::firstOrCreate(['estatus' => 'por aprobar']);

        Atencion::firstOrCreate(
            [
                'id_cliente' => $cliente->id,
                'id_personal' => $personal->id,
                'id_origen_atencion' => $origen->id,
                'asunto' => 'Solicitud de informacion',
            ],
            [
                'notas_adicionales' => 'Contacto inicial desde redes.',
                'estatus' => $estatus->id,
                'borrado_logico' => false,
            ]
        );
    }
}
