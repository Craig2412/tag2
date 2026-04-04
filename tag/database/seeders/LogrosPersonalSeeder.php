<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogrosPersonalSeeder extends Seeder
{
    public function run()
    {
        DB::table('logros_personal')->insert([
            [
                'id_personal' => 1,
                'tipo_entidad' => 'meta',
                'id_entidad' => 1,
                'id_estatus_anterior' => 1,
                'id_estatus_nuevo' => 2,
                'tiempo_transcurrido_segundos' => 3600,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_personal' => 2,
                'tipo_entidad' => 'reconocimiento',
                'id_entidad' => 2,
                'id_estatus_anterior' => 1,
                'id_estatus_nuevo' => 2,
                'tiempo_transcurrido_segundos' => 1800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
