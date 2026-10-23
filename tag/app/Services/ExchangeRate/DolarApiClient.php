<?php

namespace App\Services\ExchangeRate;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for DolarAPI Venezuela.
 * Documentation: https://ve.dolarapi.com/openapi.json
 */
class DolarApiClient
{
    private const PATHS = [
        'usd_official' => '/v1/dolares/oficial',
        'eur_official' => '/v1/euros/oficial',
        'usd_parallel' => '/v1/dolares/paralelo',
    ];

    public function usdOfficial(): array
    {
        return $this->quote('usd_official');
    }

    public function eurOfficial(): array
    {
        return $this->quote('eur_official');
    }

    public function usdParallel(): array
    {
        return $this->quote('usd_parallel');
    }

    /**
     * Returns the normalized quote for the given source.
     *
     * @param  string  $source  One of the PATHS keys.
     * @return array{source: string, value: float, updated_at: string}
     */
    private function quote(string $source): array
    {
        $baseUrl = rtrim((string) config('services.dolarapi.base_url', ''), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Configura DOLARAPI_BASE_URL en el archivo .env.');
        }

        $response = Http::timeout((int) config('services.dolarapi.timeout', 10))
            ->retry(2, 300)
            ->acceptJson()
            ->get($baseUrl.self::PATHS[$source]);

        if ($response->failed()) {
            throw new RuntimeException("DolarAPI respondió HTTP {$response->status()} para {$source}.");
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['promedio']) || ! is_numeric($payload['promedio'])) {
            throw new RuntimeException("DolarAPI devolvió una respuesta inválida para {$source}.");
        }

        return [
            'source' => $source,
            'value' => (float) $payload['promedio'],
            'updated_at' => (string) ($payload['fechaActualizacion'] ?? ''),
        ];
    }
}
