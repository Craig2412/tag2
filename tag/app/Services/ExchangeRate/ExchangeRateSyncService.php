<?php

namespace App\Services\ExchangeRate;

use App\Models\ConfiguracionSistema;
use App\Models\Tasa;
use App\Models\TasaCambio;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Pulls daily quotes from DolarAPI and stores them in tasas_cambio.
 * Catalog codes map to the quote each one represents in the business.
 */
class ExchangeRateSyncService
{
    public const SOURCES_BY_CATALOG_CODE = [
        'USD_BCV' => 'usd_official',
        'EUR_BCV' => 'eur_official',
        'BINANCE' => 'usd_parallel',
    ];

    private const CARACAS_TIMEZONE = 'America/Caracas';

    public function __construct(private readonly DolarApiClient $client)
    {
    }

    /**
     * Returns the singleton system configuration row.
     */
    public static function configuration(): ConfiguracionSistema
    {
        return ConfiguracionSistema::query()->firstOrCreate([]);
    }

    /**
     * Tells whether the automatic sync should run now.
     */
    public static function isDue(ConfiguracionSistema $configuration): bool
    {
        if (! $configuration->tasas_sync_enabled) {
            return false;
        }

        $interval = (int) $configuration->tasas_sync_interval_minutes;

        if ($interval <= 0) {
            return false;
        }

        if (! $configuration->tasas_sync_last_run_at) {
            return true;
        }

        return $configuration->tasas_sync_last_run_at->addMinutes($interval)->isPast();
    }

    /**
     * Fetches every configured quote and upserts it for the current local date.
     *
     * @return array{created: int, updated: int, failed: int, items: array<int, array>, errors: array<int, string>}
     */
    public function syncNow(): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'items' => [],
            'errors' => [],
        ];

        $catalogs = Tasa::query()
            ->whereIn('codigo', array_keys(self::SOURCES_BY_CATALOG_CODE))
            ->get()
            ->keyBy('codigo');

        foreach (self::SOURCES_BY_CATALOG_CODE as $code => $source) {
            $catalog = $catalogs->get($code);

            if (! $catalog) {
                continue;
            }

            try {
                $quote = $this->fetchQuote($source);
                $date = $this->localDate($quote['updated_at']);
                $isCreated = $this->upsertRate($catalog->id, $date, $quote['value']);

                $result[$isCreated ? 'created' : 'updated']++;
                $result['items'][] = [
                    'code' => $code,
                    'value' => round($quote['value'], 4),
                    'date' => $date,
                    'action' => $isCreated ? 'created' : 'updated',
                ];
            } catch (Throwable $exception) {
                $result['failed']++;
                $result['errors'][] = "{$code}: {$exception->getMessage()}";

                Log::warning('exchange_rates.sync_failed', [
                    'catalog_id' => $catalog->id,
                    'source' => $source,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->registerRun($result);

        return $result;
    }

    private function fetchQuote(string $source): array
    {
        return match ($source) {
            'usd_official' => $this->client->usdOfficial(),
            'eur_official' => $this->client->eurOfficial(),
            'usd_parallel' => $this->client->usdParallel(),
            default => throw new RuntimeException("Fuente desconocida: {$source}."),
        };
    }

    /**
     * Creates or updates the rate of the given catalog for a single day.
     *
     * @return bool True when a new record was created.
     */
    private function upsertRate(int $catalogId, string $date, float $value): bool
    {
        if ($value <= 0) {
            throw new RuntimeException('La cotización recibida no es válida.');
        }

        $rate = TasaCambio::withTrashed()->firstOrNew([
            'id_tasa' => $catalogId,
            'fecha' => $date,
        ]);

        $isCreated = ! $rate->exists;

        $rate->valor_cambio = round($value, 4);
        $rate->deleted_at = null;
        $rate->save();

        return $isCreated;
    }

    /**
     * Converts the API timestamp to the Venezuelan calendar date.
     */
    private function localDate(string $updatedAt): string
    {
        if ($updatedAt === '') {
            return CarbonImmutable::now(self::CARACAS_TIMEZONE)->toDateString();
        }

        return CarbonImmutable::parse($updatedAt)
            ->setTimezone(self::CARACAS_TIMEZONE)
            ->toDateString();
    }

    private function registerRun(array $result): void
    {
        $succeeded = $result['created'] + $result['updated'];
        $status = match (true) {
            $result['failed'] === 0 => 'success',
            $succeeded > 0 => 'partial',
            default => 'error',
        };

        $message = "{$result['created']} creadas, {$result['updated']} actualizadas, {$result['failed']} fallidas.";

        self::configuration()->update([
            'tasas_sync_last_run_at' => now(),
            'tasas_sync_last_status' => $status,
            'tasas_sync_last_message' => $message,
        ]);
    }
}
