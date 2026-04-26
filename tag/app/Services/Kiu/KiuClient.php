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

        $payload = $this->resolvePayload($operation, $payload);
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
            throw new RuntimeException('No fue posible conectar con KIU: '.$exception->getMessage(), previous: $exception);
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

    private function resolvePayload(string $operation, array $payload): array
    {
        $xmlPayload = $this->extractXmlPayload($payload['payload'] ?? null);

        if ($this->isMeaningfulXmlPayload($xmlPayload)) {
            return $payload;
        }

        $context = (array) ($payload['context'] ?? []);

        $generatedXml = match ($operation) {
            'availability' => $this->buildAvailabilityXml($context),
            'pricing' => $this->buildPricingXml($context),
            default => $this->buildUnsupportedOperationMessage($operation),
        };

        $payload['payload'] = $generatedXml;

        return $payload;
    }

    private function extractXmlPayload(mixed $payload): string
    {
        if (is_string($payload)) {
            return trim($payload);
        }

        if (is_array($payload)) {
            if (isset($payload['request']) && is_string($payload['request'])) {
                return trim($payload['request']);
            }

            if (isset($payload['payload']) && is_string($payload['payload'])) {
                return trim($payload['payload']);
            }
        }

        return '';
    }

    private function isMeaningfulXmlPayload(string $xmlPayload): bool
    {
        if ($xmlPayload === '' || !str_starts_with($xmlPayload, '<')) {
            return false;
        }

        $normalized = preg_replace('/\s+/', '', $xmlPayload) ?? $xmlPayload;

        return !in_array($normalized, ['<KIURequest></KIURequest>', '<KIURequest/>'], true);
    }

    private function buildAvailabilityXml(array $context): string
    {
        $origin = $this->requireContext($context, 'origin', 'availability');
        $destination = $this->requireContext($context, 'destination', 'availability');
        $departureDate = $this->normalizeDate($this->requireContext($context, 'departure_date', 'availability'));
        $airline = strtoupper(trim((string) ($context['airline'] ?? '')));
        $directFlightsOnly = $this->asXmlBool($context['direct_flights_only'] ?? false);
        $maxResponses = max(1, (int) ($context['max_responses'] ?? 10));
        $combinedItineraries = $this->asXmlBool($context['combined_itineraries'] ?? false);

        $attributes = [
            'EchoToken' => '1',
            'TimeStamp' => $this->timestamp(),
            'Target' => $this->target(),
            'Version' => $this->version(),
            'SequenceNmbr' => '1',
            'PrimaryLangID' => $this->primaryLang(),
            'DirectFlightsOnly' => $directFlightsOnly,
            'MaxResponses' => (string) $maxResponses,
            'CombinedItineraries' => $combinedItineraries,
        ];

        $specificFlightInfo = $airline !== ''
            ? '<SpecificFlightInfo><Airline Code="'.$this->xml($airline).'" /></SpecificFlightInfo>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<KIU_AirAvailRQ '.$this->xmlAttributes($attributes).'>'
            .'<POS>'
            .'<Source '.$this->xmlAttributes($this->sourceAttributes()).' />'
            .'</POS>'
            .$specificFlightInfo
            .'<OriginDestinationInformation>'
            .'<DepartureDateTime>'.$this->xml($departureDate).'</DepartureDateTime>'
            .'<OriginLocation LocationCode="'.$this->xml(strtoupper($origin)).'" />'
            .'<DestinationLocation LocationCode="'.$this->xml(strtoupper($destination)).'" />'
            .'</OriginDestinationInformation>'
            .'</KIU_AirAvailRQ>';
    }

    private function buildPricingXml(array $context): string
    {
        $segment = is_array($context['segment'] ?? null) ? $context['segment'] : [];
        $departureDateTime = $this->firstFilled($segment, $context, ['departure_datetime', 'departureDateTime']);
        $arrivalDateTime = $this->firstFilled($segment, $context, ['arrival_datetime', 'arrivalDateTime']);
        $flightNumber = $this->firstFilled($segment, $context, ['flight_number', 'flightNumber']);
        $bookingClass = $this->firstFilled($segment, $context, ['booking_class', 'res_book_desig_code', 'class']);
        $marketingAirline = $this->firstFilled($segment, $context, ['marketing_airline', 'marketingAirline', 'airline']);
        $origin = $this->firstFilled($segment, $context, ['origin', 'departure_airport', 'departureAirport']);
        $destination = $this->firstFilled($segment, $context, ['destination', 'arrival_airport', 'arrivalAirport']);

        foreach ([
            'departure_datetime' => $departureDateTime,
            'arrival_datetime' => $arrivalDateTime,
            'flight_number' => $flightNumber,
            'booking_class' => $bookingClass,
            'marketing_airline' => $marketingAirline,
            'origin' => $origin,
            'destination' => $destination,
        ] as $field => $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new RuntimeException("Falta context.{$field} para construir automaticamente el XML de pricing.");
            }
        }

        $currency = strtoupper((string) ($context['currency'] ?? config('services.kiu.iso_currency', 'USD')));
        $tourCode = trim((string) ($context['tour_code'] ?? 'JUANTEST'));
        $adults = max(0, (int) ($context['adults'] ?? 1));
        $children = max(0, (int) ($context['children'] ?? 0));
        $infants = max(0, (int) ($context['infants'] ?? 0));

        $travelerAvail = $this->passengerTag('ADT', $adults)
            .$this->passengerTag('CNN', $children)
            .$this->passengerTag('INF', $infants);

        return '<KIU_AirPriceRQ '.$this->xmlAttributes([
            'EchoToken' => '1',
            'TimeStamp' => $this->timestamp(),
            'Target' => $this->target(),
            'Version' => $this->version(),
            'SequenceNmbr' => '1',
            'PrimaryLangID' => $this->primaryLang(),
        ]).'>'
            .'<POS>'
            .'<Source '.$this->xmlAttributes($this->sourceAttributes(['ISOCurrency' => $currency])).'>'
            .'<RequestorID Type="'.$this->xml(config('services.kiu.requestor_type', '5')).'" />'
            .'<BookingChannel Type="'.$this->xml(config('services.kiu.booking_channel_type', '1')).'" />'
            .'</Source>'
            .'</POS>'
            .'<AirItinerary>'
            .'<OriginDestinationOptions>'
            .'<OriginDestinationOption>'
            .'<FlightSegment '.$this->xmlAttributes([
                'DepartureDateTime' => $departureDateTime,
                'ArrivalDateTime' => $arrivalDateTime,
                'FlightNumber' => $flightNumber,
                'ResBookDesigCode' => strtoupper($bookingClass),
            ]).'>'
            .'<DepartureAirport LocationCode="'.$this->xml(strtoupper($origin)).'" />'
            .'<ArrivalAirport LocationCode="'.$this->xml(strtoupper($destination)).'" />'
            .'<MarketingAirline Code="'.$this->xml(strtoupper($marketingAirline)).'" CompanyShortName="'.$this->xml(strtoupper($marketingAirline)).'" />'
            .'</FlightSegment>'
            .'</OriginDestinationOption>'
            .'</OriginDestinationOptions>'
            .'</AirItinerary>'
            .'<TravelerInfoSummary>'
            .'<PriceRequestInformation>'
            .'<TPA_Extension><TourCode Type="N" Code="'.$this->xml($tourCode).'" /></TPA_Extension>'
            .'</PriceRequestInformation>'
            .'<AirTravelerAvail>'.$travelerAvail.'</AirTravelerAvail>'
            .'</TravelerInfoSummary>'
            .'</KIU_AirPriceRQ>';
    }

    private function buildUnsupportedOperationMessage(string $operation): never
    {
        throw new RuntimeException(
            "No se puede construir automaticamente el XML de {$operation} con el contexto actual. Envia un payload XML KIU valido o amplia el contexto requerido para esa operacion."
        );
    }

    private function requireContext(array $context, string $key, string $operation): string
    {
        $value = $context[$key] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Falta context.{$key} para construir automaticamente el XML de {$operation}.");
        }

        return trim($value);
    }

    private function firstFilled(array $primary, array $fallback, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $primary[$key] ?? $fallback[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function passengerTag(string $code, int $quantity): string
    {
        if ($quantity <= 0) {
            return '';
        }

        return '<PassengerTypeQuantity Code="'.$this->xml($code).'" Quantity="'.$quantity.'" />';
    }

    private function timestamp(): string
    {
        return date(DATE_ATOM);
    }

    private function target(): string
    {
        return (string) config('services.kiu.target', 'Production');
    }

    private function version(): string
    {
        return (string) config('services.kiu.version', '3.0');
    }

    private function primaryLang(): string
    {
        return (string) config('services.kiu.primary_lang', 'en-us');
    }

    private function sourceAttributes(array $extra = []): array
    {
        return array_merge([
            'AgentSine' => (string) config('services.kiu.auth.agent_sine', ''),
            'TerminalID' => (string) config('services.kiu.auth.office_id', ''),
            'ISOCountry' => (string) config('services.kiu.iso_country', 'PA'),
        ], $extra);
    }

    private function normalizeDate(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date('Y-m-d', $timestamp);
    }

    private function asXmlBool(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOL) ? 'true' : 'false';
    }

    private function xmlAttributes(array $attributes): string
    {
        $pairs = [];

        foreach ($attributes as $name => $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }

            $pairs[] = $name.'="'.$this->xml($value).'"';
        }

        return implode(' ', $pairs);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
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