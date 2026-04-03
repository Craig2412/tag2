<?php

namespace App\Services\Kiu;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KiuClient
{
    public function session(array $payload): array
    {
        return $this->send('session', $payload);
    }

    public function availability(array $payload): array
    {
        return $this->send('availability', $payload);
    }

    public function pricing(array $payload): array
    {
        return $this->send('pricing', $payload);
    }

    public function booking(array $payload): array
    {
        return $this->send('booking', $payload);
    }

    public function ticketing(array $payload): array
    {
        return $this->send('ticketing', $payload);
    }

    public function postSale(array $payload): array
    {
        return $this->send('post_sale', $payload);
    }

    private function send(string $operation, array $payload): array
    {
        $baseUrl = rtrim((string) config('services.kiu.base_url', ''), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('Configura KIU_BASE_URL con la URL sandbox entregada por KIU.');
        }

        $operationConfig = (array) config("services.kiu.operations.{$operation}", []);
        $url = $this->buildUrl($baseUrl, (string) ($operationConfig['path'] ?? ''));
        $transport = (string) ($payload['transport'] ?? $operationConfig['transport'] ?? config('services.kiu.transport', 'xml'));
        $contentType = (string) ($operationConfig['content_type'] ?? 'text/xml; charset=utf-8');
        $timeout = (int) ($payload['timeout'] ?? config('services.kiu.timeout', 30));
        $verify = filter_var($payload['verify'] ?? config('services.kiu.verify', true), FILTER_VALIDATE_BOOL);
        $query = array_merge((array) ($operationConfig['query'] ?? []), (array) ($payload['query'] ?? []));
        $headers = array_merge(
            (array) config('services.kiu.default_headers', []),
            (array) ($operationConfig['headers'] ?? []),
            (array) ($payload['headers'] ?? [])
        );

        if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = $contentType;
        }

        $soapAction = $payload['soap_action'] ?? $operationConfig['soap_action'] ?? null;
        if (is_string($soapAction) && $soapAction !== '') {
            $headers['SOAPAction'] = $soapAction;
        }

        [$headers, $query] = $this->applyAuthentication($headers, $query, (array) ($payload['credentials'] ?? []));

        $request = Http::withHeaders($headers)
            ->timeout($timeout)
            ->withOptions([
                'verify' => $verify,
                'query' => $query,
            ]);

        try {
            $response = match ($transport) {
                'json' => $request->post($url, $this->normalizeStructuredPayload($payload['payload'])),
                'form_params' => $request->asForm()->post($url, $this->prepareKiuFormPayload($payload['payload'] ?? null)),
                default => $request->withBody((string) $payload['payload'], $headers['Content-Type'])->send('POST', $url),
            };
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No fue posible conectar con KIU sandbox: '.$exception->getMessage(), previous: $exception);
        }

        $result = [
            'ok' => $response->successful(),
            'operation' => $operation,
            'sandbox' => (bool) config('services.kiu.sandbox', true),
            'request' => [
                'url' => $url,
                'transport' => $transport,
                'query' => $this->sanitizeArray($query),
                'headers' => $this->sanitizeArray($headers),
                'context' => $this->sanitizeArray((array) ($payload['context'] ?? [])),
            ],
            'response' => [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'data' => $this->parseResponseBody($response->body(), (string) $response->header('Content-Type')),
            ],
        ];

        Log::info('kiu.operation', [
            'operation' => $operation,
            'sandbox' => $result['sandbox'],
            'status' => $result['response']['status'],
            'url' => $url,
        ]);

        return $result;
    }

    private function applyAuthentication(array $headers, array $query, array $credentials): array
    {
        $authMode = (string) config('services.kiu.auth.mode', 'none');
        $auth = array_merge((array) config('services.kiu.auth', []), $credentials);

        if ($authMode !== 'header') {
            return [$headers, $query];
        }

        $headerMap = (array) ($auth['headers'] ?? []);
        $pairs = [
            'username' => $auth['username'] ?? null,
            'password' => $auth['password'] ?? null,
            'office_id' => $auth['office_id'] ?? null,
            'agent_sine' => $auth['agent_sine'] ?? null,
        ];

        foreach ($pairs as $key => $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }

            $headerName = $headerMap[$key] ?? null;
            if (is_string($headerName) && $headerName !== '') {
                $headers[$headerName] = $value;
            }
        }

        return [$headers, $query];
    }

    private function buildUrl(string $baseUrl, string $path): string
    {
        if ($path !== '' && preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    private function normalizeStructuredPayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (!is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : ['payload' => $payload];
    }

    private function prepareKiuFormPayload(mixed $payload): array
    {
        $normalized = $this->normalizeStructuredPayload($payload);

        if (array_key_exists('user', $normalized) && array_key_exists('password', $normalized) && array_key_exists('request', $normalized)) {
            return $normalized;
        }

        $xmlRequest = '';

        if (is_string($payload) && trim($payload) !== '') {
            $xmlRequest = $payload;
        } elseif (isset($normalized['request']) && is_string($normalized['request'])) {
            $xmlRequest = $normalized['request'];
        } elseif (isset($normalized['payload']) && is_string($normalized['payload'])) {
            $xmlRequest = $normalized['payload'];
        }

        return array_filter([
            'user' => (string) config('services.kiu.auth.username', config('services.kiu.username', '')),
            'password' => (string) config('services.kiu.auth.password', config('services.kiu.password', '')),
            'request' => $xmlRequest,
        ], static fn ($value) => is_string($value) && $value !== '');
    }

    private function parseResponseBody(string $body, string $contentType): mixed
    {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return null;
        }

        if (str_contains(strtolower($contentType), 'json')) {
            $decoded = json_decode($body, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        if (
            str_contains(strtolower($contentType), 'xml')
            || str_starts_with($trimmed, '<')
        ) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
            libxml_clear_errors();

            if ($xml !== false) {
                return json_decode(json_encode($xml), true);
            }
        }

        return null;
    }

    private function sanitizeArray(array $data): array
    {
        $sensitiveKeys = ['authorization', 'password', 'x-kiu-password', 'soapaction'];
        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}