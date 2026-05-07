<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\OrdenCompra;
use App\Models\Personal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogrosPersonalSeeder extends Seeder
{
    public function run()
    {
        $personal = Personal::first();

        if (!$personal) {
            return;
        }

        // Busca IDs reales de entidades en BD para que el progreso monetario funcione.
        // Si no existen, usa 0 como fallback (el logro existe pero no suma monto).
        $idAtencion    = Atencion::value('id')    ?? 0;
        $idOrdenCompra = OrdenCompra::value('id') ?? 0;

        DB::table('logros_personal')->insert([
            [
                // Simular que el vendedor aprobó una Atención hoy
                'id_personal'                  => $personal->id,
                'tipo_entidad'                 => 'atencion',
                'id_entidad'                   => $idAtencion,
                'estatus_anterior'             => 'por_aprobar',
                'estatus_nuevo'                => 'aprobado',
                'tiempo_transcurrido_segundos' => 1800,
                'created_at'                   => now(),
                'updated_at'                   => now(),
            ],
            [
                // Simular que el vendedor cerró una Orden de Compra pagada hoy
                'id_personal'                  => $personal->id,
                'tipo_entidad'                 => 'orden_compra',
                'id_entidad'                   => $idOrdenCompra,
                'estatus_anterior'             => 'pendiente',
                'estatus_nuevo'                => 'pagado',
                'tiempo_transcurrido_segundos' => 3600,
                'created_at'                   => now(),
                'updated_at'                   => now(),
            ],
        ]);
    }
}
