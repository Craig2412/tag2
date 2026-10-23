<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Podar logs antiguos diariamente ───────────────────────────
Schedule::command('app:prune-logs --days=90')->daily();

// ── Sincronizar tasas de cambio ──────────────────────────────
// Se evalúa cada minuto; el comando omite la ejecución si no se cumplió el intervalo configurado.
Schedule::command('app:sync-exchange-rates')->everyMinute()->withoutOverlapping();
