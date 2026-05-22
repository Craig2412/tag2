<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\Cliente;
use App\Models\EstadoAtencion;
use App\Models\Origen;
use App\Models\Personal;
use Illuminate\Database\Seeder;

class AtencionesSeeder extends Seeder
{
    public function run(): void
    {
        $cliente = Cliente::first();
        $personal = Personal::first();
        $origen = Origen::first();

        if (! $cliente || ! $personal || ! $origen) {
            return;
        }

        $estado = EstadoAtencion::where('slug', 'abierta')->first();

        Atencion::firstOrCreate(
            [
                'id_cliente' => $cliente->id,
                'id_personal' => $personal->id,
                'id_origen_atencion' => $origen->id,
                'asunto' => 'Solicitud de informacion',
            ],
            [
                'notas_adicionales' => 'Contacto inicial desde redes.',
                'id_estado_atencion' => $estado ? $estado->id : 1,
            ]
        );
    }
}
