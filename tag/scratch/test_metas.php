<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MetaPersonal;

echo "--- ESTADO DE METAS ACTUALES ---\n";

$asignaciones = MetaPersonal::with(['meta', 'personal'])->get();

foreach ($asignaciones as $asig) {
    echo "\nEmpleado: {$asig->personal->nombre}\n";
    echo "Meta: {$asig->meta->nombre}\n";
    echo 'Tipo: '.($asig->meta->es_monetario ? 'Monetaria' : 'Conteo')."\n";
    echo 'Objetivo: '.($asig->meta->es_monetario ? '$' : '').number_format($asig->meta->valor_objetivo, 2)."\n";
    echo 'Progreso Actual: '.($asig->meta->es_monetario ? '$' : '').number_format($asig->progreso_actual, 2)."\n";

    $porcentaje = ($asig->progreso_actual / $asig->meta->valor_objetivo) * 100;
    echo 'Cumplimiento: '.number_format($porcentaje, 2)."%\n";
    echo "-------------------------------\n";
}
