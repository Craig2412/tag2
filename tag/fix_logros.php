<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$personalId = \App\Models\Personal::value('id');
if (!$personalId) die("No hay personal");

$atId = DB::table('atenciones')->value('id');
$ocId = DB::table('ordenes_compra')->value('id');

// Insertar historial para los últimos 3 periodos
for ($i = 1; $i <= 3; $i++) {
    // Atenciones (mes pasado, hace 2 meses, etc)
    $fechaAtencion = now()->subMonths($i)->startOfMonth()->addDays(5);
    
    // Órdenes (semana pasada, hace 2 semanas, etc)
    $fechaOc = now()->subWeeks($i)->startOfWeek()->addDays(2);

    DB::table('logros_personal')->insert([
        [
            'id_personal'                  => $personalId,
            'tipo_entidad'                 => 'atencion',
            'id_entidad'                   => $atId ?? 0,
            'estatus_anterior'             => 'por_aprobar',
            'estatus_nuevo'                => 'aprobado',
            'tiempo_transcurrido_segundos' => 1800,
            'created_at'                   => $fechaAtencion,
            'updated_at'                   => $fechaAtencion,
        ],
        [
            'id_personal'                  => $personalId,
            'tipo_entidad'                 => 'orden_compra',
            'id_entidad'                   => $ocId ?? 0,
            'estatus_anterior'             => 'pendiente',
            'estatus_nuevo'                => 'pagado',
            'tiempo_transcurrido_segundos' => 3600,
            'created_at'                   => $fechaOc,
            'updated_at'                   => $fechaOc,
        ],
    ]);
}

echo "Histórico insertado." . PHP_EOL;
