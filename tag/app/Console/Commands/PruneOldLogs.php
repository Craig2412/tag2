<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOldLogs extends Command
{
    protected $signature = 'app:prune-logs {--days=90 : Días de retención}';

    protected $description = 'Elimina registros de auditoría y logros antiguos para evitar crecimiento ilimitado';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        // Podar audit_logs
        $deletedAudit = DB::table('audit_logs')
            ->where('created_at', '<', $cutoff)
            ->delete();

        // Podar logros_personal
        $deletedLogros = DB::table('logros_personal')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Poda completada: {$deletedAudit} audit_logs y {$deletedLogros} logros_personal anteriores a {$cutoff->toDateString()} eliminados.");

        return self::SUCCESS;
    }
}
