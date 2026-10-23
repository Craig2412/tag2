<?php

namespace App\Console\Commands;

use App\Services\ExchangeRate\ExchangeRateSyncService;
use Illuminate\Console\Command;

class SyncExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-exchange-rates
                            {--force : Ejecuta la sincronización sin respetar el intervalo configurado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las tasas de cambio (USD BCV, EUR BCV y paralelo) desde DolarAPI';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateSyncService $syncService): int
    {
        $configuration = ExchangeRateSyncService::configuration();

        if (! $this->option('force') && ! ExchangeRateSyncService::isDue($configuration)) {
            $this->info('Sincronización omitida: aún no corresponde según el intervalo configurado.');

            return self::SUCCESS;
        }

        $result = $syncService->syncNow();

        $this->table(
            ['Código', 'Valor', 'Fecha', 'Acción'],
            array_map(
                fn (array $item) => [$item['code'], $item['value'], $item['date'], $item['action']],
                $result['items']
            )
        );

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->info("Creadas: {$result['created']} | Actualizadas: {$result['updated']} | Fallidas: {$result['failed']}");

        return $result['failed'] > 0 && ($result['created'] + $result['updated']) === 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
