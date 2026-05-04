<?php

namespace Database\Seeders;

use App\Models\Personal;
use App\Models\Estatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogrosPersonalSeeder extends Seeder
{
    public function run()
    {
        $personal = Personal::first();
        $estatusAprobado = Estatus::where('estatus', 'aprobado')->first();
        $estatusPagado = Estatus::where('estatus', 'pagado')->first();

        if (!$personal || !$estatusAprobado || !$estatusPagado) {
            return;
        }

        // Simular que el vendedor aprobó una Atención hoy
        DB::table('logros_personal')->insert([
            [
                'id_personal' => $personal->id,
                'tipo_entidad' => 'atencion',
                'id_entidad' => 1, // ID de la atención seeder
                'id_estatus_anterior' => 1,
                'id_estatus_nuevo' => $estatusAprobado->id,
                'tiempo_transcurrido_segundos' => 1800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Simular que el vendedor cerró una Orden de Compra pagada hoy
            [
                'id_personal' => $personal->id,
                'tipo_entidad' => 'orden_compra',
                'id_entidad' => 1, // ID de la orden seeder
                'id_estatus_anterior' => 1,
                'id_estatus_nuevo' => $estatusPagado->id,
                'tiempo_transcurrido_segundos' => 3600,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
