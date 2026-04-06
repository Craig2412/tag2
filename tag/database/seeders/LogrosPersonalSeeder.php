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
        $estatusViejo = Estatus::where('estatus', 'activo')->first();
        $estatusNuevo = Estatus::where('estatus', 'completado')->first() ?? $estatusViejo;

        if (!$personal) {
            return;
        }

        DB::table('logros_personal')->insert([
            [
                'id_personal' => $personal->id,
                'tipo_entidad' => 'meta',
                'id_entidad' => 1,
                'id_estatus_anterior' => $estatusViejo ? $estatusViejo->id : 1,
                'id_estatus_nuevo' => $estatusNuevo ? $estatusNuevo->id : 1,
                'tiempo_transcurrido_segundos' => 3600,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
