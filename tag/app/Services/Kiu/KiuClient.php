<?php

namespace App\Services\Kiu;

use Illuminate\Http\Client\ConnectionException;
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

        if (! array_key_exists('Content-Type', $headers)) {
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

        // ── DEBUG: Imprime el XML enviado a KIU ──
        Log::debug('kiu.outgoing_xml', [
            'operation' => $operation,
            'xml'       => (string) ($payload['payload'] ?? ''),
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

        if (in_array($operation, ['availability', 'pricing'], true)) {
            $result['data_booking'] = $this->buildBookingPayloadFromAvailability(
                (array) ($payload['context'] ?? []),
                $result['response']['data'],
            );
        }

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
            if (! is_string($value) || $value === '') {
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
            'booking' => $this->buildBookingXml($context),
            'ticketing' => $this->buildTicketingXml($context),
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
        if ($xmlPayload === '' || ! str_starts_with($xmlPayload, '<')) {
            return false;
        }

        $normalized = preg_replace('/\s+/', '', $xmlPayload) ?? $xmlPayload;

        return ! in_array($normalized, ['<KIURequest></KIURequest>', '<KIURequest/>'], true);
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
        $currency = strtoupper((string) ($context['currency'] ?? config('services.kiu.iso_currency', 'USD')));
        $tourCode = trim((string) ($context['tour_code'] ?? 'JUANTEST'));
        $adults = max(0, (int) ($context['adults'] ?? 1));
        $children = max(0, (int) ($context['children'] ?? 0));
        $infants = max(0, (int) ($context['infants'] ?? 0));

        $posAndSource = '<POS>'
            .'<Source '.$this->xmlAttributes(array_merge(
                $this->sourceAttributes(['ISOCurrency' => $currency]),
                ['PseudoCityCode' => $this->pseudoCityCode()]
            )).'>'
            .'<RequestorID Type="'.$this->xml(config('services.kiu.requestor_type', '5')).'" />'
            .'<BookingChannel Type="'.$this->xml(config('services.kiu.booking_channel_type', '1')).'" />'
            .'</Source>'
            .'</POS>';

        $priceRequestInfo = '<TravelerInfoSummary>'
            .'<PriceRequestInformation>'
            .'<TPA_Extension><TourCode Type="N" Code="'.$this->xml($tourCode).'" /></TPA_Extension>'
            .'</PriceRequestInformation>'
            .'<AirTravelerAvail>'
            .$this->passengerTag('ADT', $adults)
            .$this->passengerTag('CNN', $children)
            .$this->passengerTag('INF', $infants)
            .'</AirTravelerAvail>'
            .'</TravelerInfoSummary>';

        // ── Pricing por PNR (solo si NO hay segmento) ──
        $reservationCode = isset($context['reservation_code'])
            ? trim((string) $context['reservation_code'])
            : '';

        $hasSegment = isset($context['segment']) && is_array($context['segment'])
            && $context['segment'] !== [];

        if ($reservationCode !== '' && ! $hasSegment) {
            return '<KIU_AirPriceRQ '.$this->xmlAttributes([
                'EchoToken'    => '1',
                'TimeStamp'    => $this->timestamp(),
                'Target'       => $this->target(),
                'Version'      => $this->version(),
                'SequenceNmbr' => '1',
                'PrimaryLangID'=> $this->primaryLang(),
            ]).'>'
                .$posAndSource
                .'<BookingReferenceID ID="'.$this->xml(strtoupper($reservationCode)).'" />'
                .$priceRequestInfo
                .'</KIU_AirPriceRQ>';
        }

        // ── Pricing por segmento de vuelo ──
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
            if (! is_string($value) || trim($value) === '') {
                throw new RuntimeException("Falta context.{$field} para construir automaticamente el XML de pricing.");
            }
        }

        return '<KIU_AirPriceRQ '.$this->xmlAttributes([
            'EchoToken' => '1',
            'TimeStamp' => $this->timestamp(),
            'Target' => $this->target(),
            'Version' => $this->version(),
            'SequenceNmbr' => '1',
            'PrimaryLangID' => $this->primaryLang(),
        ]).'>'
            .$posAndSource
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
            .$priceRequestInfo
            .'</KIU_AirPriceRQ>';
    }

    private function buildBookingXml(array $context): string
    {
        // ── Segmento de vuelo ──
        $segment = is_array($context['segment'] ?? null) ? $context['segment'] : [];
        $departureDateTime = $this->firstFilled($segment, $context, ['departure_datetime', 'departureDateTime']);
        $arrivalDateTime   = $this->firstFilled($segment, $context, ['arrival_datetime', 'arrivalDateTime']);
        $flightNumber      = $this->firstFilled($segment, $context, ['flight_number', 'flightNumber']);
        $bookingClass      = $this->firstFilled($segment, $context, ['booking_class', 'res_book_desig_code', 'class']);
        $marketingAirline  = $this->firstFilled($segment, $context, ['marketing_airline', 'marketingAirline', 'airline']);
        $origin            = $this->firstFilled($segment, $context, ['origin', 'departure_airport', 'departureAirport']);
        $destination       = $this->firstFilled($segment, $context, ['destination', 'arrival_airport', 'arrivalAirport']);

        foreach ([
            'departure_datetime' => $departureDateTime,
            'arrival_datetime'   => $arrivalDateTime,
            'flight_number'      => $flightNumber,
            'booking_class'      => $bookingClass,
            'marketing_airline'  => $marketingAirline,
            'origin'             => $origin,
            'destination'        => $destination,
        ] as $field => $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new RuntimeException("Falta context.{$field} para construir automaticamente el XML de booking.");
            }
        }

        // ── Pasajeros ──
        $passengers = $context['passengers'] ?? [];
        if (! is_array($passengers) || $passengers === []) {
            throw new RuntimeException('Falta context.passengers para construir automaticamente el XML de booking.');
        }

        $adults   = 0;
        $children = 0;
        $infants  = 0;
        $passengerXml = '';

        foreach ($passengers as $index => $p) {
            $type = strtoupper(trim((string) ($p['type'] ?? 'ADT')));
            $firstName = trim((string) ($p['first_name'] ?? ''));
            $middleName = trim((string) ($p['middle_name'] ?? ''));
            $prefix = trim((string) ($p['name_prefix'] ?? $p['prefix'] ?? ''));
            $lastName  = trim((string) ($p['last_name'] ?? ''));
            $birthDate = trim((string) ($p['birth_date'] ?? $p['birthDate'] ?? ''));
            $telephoneArea = trim((string) ($p['telephone_area_city_code'] ?? $p['phone_area_code'] ?? $p['area_city_code'] ?? ''));
            $telephoneNumber = trim((string) ($p['telephone_phone_number'] ?? $p['phone_number'] ?? $p['telephone'] ?? $p['phone'] ?? ''));
            $email = trim((string) ($p['email'] ?? ''));
            $documentId = trim((string) ($p['document_id'] ?? $p['doc_id'] ?? ''));
            $documentType = trim((string) ($p['document_type'] ?? $p['doc_type'] ?? ''));
            $loyaltyProgram = trim((string) ($p['loyalty_program_id'] ?? $p['program_id'] ?? ''));
            $loyaltyMembership = trim((string) ($p['loyalty_membership_id'] ?? $p['membership_id'] ?? ''));
            $travelerRef = trim((string) ($p['traveler_ref_number'] ?? $p['rph'] ?? ''));

            if ($firstName === '' || $lastName === '') {
                continue;
            }

            match ($type) {
                'ADT' => $adults++,
                'CNN' => $children++,
                'INF' => $infants++,
                default => $adults++,
            };

            $passengerXml .= '<AirTraveler PassengerTypeCode="'.$this->xml($type).'"'.($birthDate !== '' ? ' BirthDate="'.$this->xml($birthDate).'"' : '').'>'
                .'<PassengerTypeQuantity Code="'.$this->xml($type).'" Quantity="1" />'
                .'<PersonName>';

            if ($prefix !== '') {
                $passengerXml .= '<NamePrefix>'.$this->xml($prefix).'</NamePrefix>';
            }

            $passengerXml .= '<GivenName>'.$this->xml($firstName).'</GivenName>';

            if ($middleName !== '') {
                $passengerXml .= '<MiddleName>'.$this->xml($middleName).'</MiddleName>';
            }

            $passengerXml .= '<Surname>'.$this->xml($lastName).'</Surname>'
                .'</PersonName>';

            if ($telephoneArea !== '' || $telephoneNumber !== '') {
                $passengerXml .= '<Telephone'.($telephoneArea !== '' ? ' AreaCityCode="'.$this->xml($telephoneArea).'"' : '').($telephoneNumber !== '' ? ' PhoneNumber="'.$this->xml($telephoneNumber).'"' : '').' />';
            }

            if ($email !== '') {
                $passengerXml .= '<Email>'.$this->xml($email).'</Email>';
            }

            if ($documentId !== '' || $documentType !== '') {
                $passengerXml .= '<Document'.($documentId !== '' ? ' DocID="'.$this->xml($documentId).'"' : '').($documentType !== '' ? ' DocType="'.$this->xml($documentType).'"' : '').' />';
            }

            if ($loyaltyProgram !== '' || $loyaltyMembership !== '') {
                $passengerXml .= '<CustoLoyalty'.($loyaltyProgram !== '' ? ' ProgramID="'.$this->xml($loyaltyProgram).'"' : '').($loyaltyMembership !== '' ? ' MembershipID="'.$this->xml($loyaltyMembership).'"' : '').' />';
            }

            if ($travelerRef !== '') {
                $passengerXml .= '<TravelerRefNumber RPH="'.$this->xml($travelerRef).'" />';
            }

            $passengerXml .= '</AirTraveler>';
        }

        if ($adults === 0 && $children === 0 && $infants === 0) {
            throw new RuntimeException('Se requiere al menos un pasajero con first_name y last_name para construir el XML de booking.');
        }

        // ── Moneda ──
        $currency = strtoupper((string) ($context['currency'] ?? config('services.kiu.iso_currency', 'USD')));
        $tourCode = trim((string) ($context['tour_code'] ?? 'JUANTEST'));

        // ── Contacto ──
        $contactEmail = trim((string) ($context['contact_email'] ?? ''));
        $contactPhone = trim((string) ($context['contact_phone'] ?? ''));
        $contactXml = '';

        if ($contactEmail !== '' || $contactPhone !== '') {
            $contactXml = '<ContactInfo><ContactPerson>';
            if ($contactEmail !== '') {
                $contactXml .= '<Email>'.$this->xml($contactEmail).'</Email>';
            }
            if ($contactPhone !== '') {
                $contactXml .= '<Telephone'.($contactPhone !== '' ? ' PhoneNumber="'.$this->xml($contactPhone).'"' : '').' />';
            }
            $contactXml .= '</ContactPerson></ContactInfo>';
        }

        $specialRequest = trim((string) ($context['special_request'] ?? $context['remark'] ?? ''));
        $specialReqXml = '';

        if ($specialRequest !== '') {
            $specialReqXml = '<SpecialReqDetails><Remarks><Remark>'.$this->xml($specialRequest).'</Remark></Remarks></SpecialReqDetails>';
        }

        // ── TicketTimeLimit ──
        $ticketTimeLimit = trim((string) ($context['ticket_time_limit'] ?? '3'));
        $ticketTimeLimitXml = '<Ticketing TicketTimeLimit="'.$this->xml($ticketTimeLimit).'" />';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<KIU_AirBookRQ '.$this->xmlAttributes([
                'EchoToken'    => '1',
                'TimeStamp'    => $this->timestamp(),
                'Target'       => $this->target(),
                'Version'      => $this->version(),
                'SequenceNmbr' => '1',
                'PrimaryLangID'=> $this->primaryLang(),
            ]).'>'
            .'<POS>'
            .'<Source '.$this->xmlAttributes(array_merge(
                $this->sourceAttributes(['ISOCurrency' => $currency]),
                ['PseudoCityCode' => $this->pseudoCityCode()]
            )).'>'
            .'<RequestorID Type="'.$this->xml(config('services.kiu.requestor_type', '5')).'" />'
            .'<BookingChannel Type="'.$this->xml(config('services.kiu.booking_channel_type', '1')).'" />'
            .'</Source>'
            .'</POS>'
            .'<PriceInfo>'
            .'<TourCode Type="N" Text="'.$this->xml($tourCode).'" />'
            .'</PriceInfo>'
            .'<AirItinerary>'
            .'<OriginDestinationOptions>'
            .'<OriginDestinationOption>'
            .'<FlightSegment '.$this->xmlAttributes([
                'DepartureDateTime' => $departureDateTime,
                'ArrivalDateTime'   => $arrivalDateTime,
                'FlightNumber'      => $flightNumber,
                'ResBookDesigCode'  => strtoupper($bookingClass),
            ]).'>'
            .'<DepartureAirport LocationCode="'.$this->xml(strtoupper($origin)).'" />'
            .'<ArrivalAirport LocationCode="'.$this->xml(strtoupper($destination)).'" />'
            .'<MarketingAirline Code="'.$this->xml(strtoupper($marketingAirline)).'" />'
            .'</FlightSegment>'
            .'</OriginDestinationOption>'
            .'</OriginDestinationOptions>'
            .'</AirItinerary>'
            .'<TravelerInfo>'
            .$passengerXml
            .$contactXml
            .$specialReqXml
            .'</TravelerInfo>'
            .$ticketTimeLimitXml
            .'</KIU_AirBookRQ>';
    }

    private function buildTicketingXml(array $context): string
    {
        $reservationCode = $this->requireContext($context, 'reservation_code', 'ticketing');
        $currency = strtoupper((string) ($context['currency'] ?? config('services.kiu.iso_currency', 'USD')));
        $formOfPayment = strtoupper(trim((string) ($context['form_of_payment'] ?? $context['payment_method'] ?? 'CASH')));
        $validateOnly = filter_var($context['validate_only'] ?? false, FILTER_VALIDATE_BOOL);
        $ticketingControl = $validateOnly ? 'VALIDATE' : 'COMMIT';

        $endorsement = trim((string) ($context['endorsement'] ?? ''));
        $endorsementXml = '';
        if ($endorsement !== '') {
            $endorsementXml = '<Endorsement><Text>'.$this->xml($endorsement).'</Text></Endorsement>';
        }

        // ── PricingInfo (datos de tarifa del pricing previo) ──
        $pricingInfo = is_array($context['pricing_info'] ?? null) ? $context['pricing_info'] : [];
        $pricingInfoXml = '';
        $totalFare = $pricingInfo['total_fare'] ?? $pricingInfo['totalFare'] ?? null;
        $baseFare  = $pricingInfo['base_fare'] ?? $pricingInfo['baseFare'] ?? null;
        if (is_array($totalFare) && isset($totalFare['amount'])) {
            $pricingInfoXml .= '<ItinTotalFare>';

            if (is_array($baseFare) && isset($baseFare['amount'])) {
                $pricingInfoXml .= '<BaseFare Amount="'.$this->xml((string) $baseFare['amount'])
                    .'" CurrencyCode="'.$this->xml(strtoupper((string) ($baseFare['currency'] ?? $currency))).'" />';
            }

            $pricingInfoXml .= '<TotalFare Amount="'.$this->xml((string) $totalFare['amount'])
                .'" CurrencyCode="'.$this->xml(strtoupper((string) ($totalFare['currency'] ?? $currency))).'" />';

            $pricingInfoXml .= '</ItinTotalFare>';
        }
        if ($pricingInfoXml !== '') {
            $pricingInfoXml = '<PricingInfo>'.$pricingInfoXml.'</PricingInfo>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<KIU_AirDemandTicketRQ '.$this->xmlAttributes([
                'EchoToken'    => '1',
                'TimeStamp'    => $this->timestamp(),
                'Target'       => $this->target(),
                'Version'      => $this->version(),
                'SequenceNmbr' => '1',
                'PrimaryLangID'=> $this->primaryLang(),
            ]).'>'
            .'<POS>'
            .'<Source '.$this->xmlAttributes(array_merge(
                $this->sourceAttributes(['ISOCurrency' => $currency]),
                ['PseudoCityCode' => $this->pseudoCityCode()]
            )).'>'
            .'<RequestorID Type="'.$this->xml(config('services.kiu.requestor_type', '5')).'" />'
            .'<BookingChannel Type="'.$this->xml(config('services.kiu.booking_channel_type', '1')).'" />'
            .'</Source>'
            .'</POS>'
            .'<DemandTicketRQ TicketingControl="'.$this->xml($ticketingControl).'">'
            .'<BookingReferenceID Type="1" ID="'.$this->xml(strtoupper($reservationCode)).'" />'
            .'<PaymentInfo><FormOfPayment Type="'.$this->xml($formOfPayment).'" /></PaymentInfo>'
            .$endorsementXml
            .$pricingInfoXml
            .'</DemandTicketRQ>'
            .'</KIU_AirDemandTicketRQ>';
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

        if (! is_string($value) || trim($value) === '') {
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

    /**
     * PseudoCityCode para KIU WS3.
     * Usa KIU_PSEUDO_CITY_CODE del .env si está definido;
     * si no, deriva los últimos 3 caracteres del office_id (ej: PTYR37300C → 00C).
     */
    private function pseudoCityCode(): string
    {
        $fromEnv = (string) config('services.kiu.pseudo_city_code', '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $officeId = (string) config('services.kiu.auth.office_id', '');
        if (strlen($officeId) >= 3) {
            return substr($officeId, -3);
        }

        return $officeId;
    }

    /**
     * Devuelve el TicketTimeLimit para booking.
     * Si se especifica en context.ticket_time_limit usa ese valor;
     * de lo contrario usa el valor por defecto "3".
     */
    private function ticketTimeLimit(string $departureDateTime, string $override = ''): string
    {
        if ($override !== '') {
            return $override;
        }

        return '3';
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
            if (! is_string($value) || $value === '') {
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

        if (! is_string($payload) || trim($payload) === '') {
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

    private function buildBookingPayloadFromAvailability(array $context, mixed $responseData): array
    {
        $segments = $this->extractAllBookingSegmentsFromAvailability($responseData);

        if ($segments === []) {
            $fallback = $this->buildSegmentFromContext($context);
            if ($fallback !== null) {
                $segments = [[
                    'segment' => $fallback,
                    'label' => $this->segmentLabel($fallback),
                    'flights' => 1,
                ]];
            }
        }

        if ($segments === []) {
            return [];
        }

        $shared = [
            'adults' => max(0, (int) ($context['adults'] ?? 1)),
            'children' => max(0, (int) ($context['children'] ?? 0)),
            'infants' => max(0, (int) ($context['infants'] ?? 0)),
        ];

        foreach (['tour_code', 'ticket_time_limit', 'special_request', 'contact_email', 'contact_phone'] as $key) {
            if (isset($context[$key]) && trim((string) $context[$key]) !== '') {
                $shared[$key] = $context[$key];
            }
        }

        $payloads = [];
        foreach ($segments as $entry) {
            $payloads[] = [
                'label' => $entry['label'],
                'flights' => $entry['flights'],
                'context' => array_merge($shared, [
                    'segment' => $entry['segment'],
                ]),
            ];
        }

        return $payloads;
    }

    private function extractAllBookingSegmentsFromAvailability(mixed $responseData): array
    {
        if (! is_array($responseData)) {
            return [];
        }

        $options = $responseData['OriginDestinationInformation']['OriginDestinationOptions']['OriginDestinationOption'] ?? null;
        if ($options === null) {
            return [];
        }

        if (isset($options['FlightSegment']) || array_keys($options) !== range(0, count($options) - 1)) {
            $options = [$options];
        }

        $result = [];
        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }

            $flightSegments = $option['FlightSegment'] ?? null;
            if ($flightSegments === null) {
                continue;
            }

            // Normalize to array of segments (handles both direct and connecting flights)
            if (isset($flightSegments['@attributes'])) {
                $flightSegments = [$flightSegments];
            }

            if (! is_array($flightSegments) || ! isset($flightSegments[0])) {
                continue;
            }

            $segmentList = [];
            $valid = true;
            foreach ($flightSegments as $fs) {
                if (! is_array($fs)) {
                    $valid = false;
                    break;
                }

                $attrs = $fs['@attributes'] ?? [];
                $origin = $fs['DepartureAirport']['@attributes']['LocationCode'] ?? $attrs['OriginLocation'] ?? null;
                $destination = $fs['ArrivalAirport']['@attributes']['LocationCode'] ?? $attrs['DestinationLocation'] ?? null;
                $marketingAirline = $this->firstNonEmpty([
                    $fs['MarketingAirline']['@attributes']['CompanyShortName'] ?? null,
                    $fs['MarketingAirline']['@attributes']['Code'] ?? null,
                    $attrs['MarketingAirline'] ?? null,
                ]);
                $bookingClass = $this->firstBookingClassCode($fs['BookingClassAvail'] ?? null);

                if (! is_string($attrs['DepartureDateTime'] ?? null)
                    || ! is_string($attrs['ArrivalDateTime'] ?? null)
                    || ! is_string($attrs['FlightNumber'] ?? null)) {
                    $valid = false;
                    break;
                }

                if ($origin === null || $destination === null || $marketingAirline === null || $bookingClass === null) {
                    $valid = false;
                    break;
                }

                $segmentList[] = [
                    'origin' => strtoupper($origin),
                    'destination' => strtoupper($destination),
                    'departure_datetime' => $attrs['DepartureDateTime'],
                    'arrival_datetime' => $attrs['ArrivalDateTime'],
                    'flight_number' => $attrs['FlightNumber'],
                    'booking_class' => strtoupper($bookingClass),
                    'marketing_airline' => strtoupper($marketingAirline),
                ];
            }

            if (! $valid || $segmentList === []) {
                continue;
            }

            // First segment defines the main route; for multi-leg store all segments
            $first = $segmentList[0];
            $result[] = [
                'segment' => count($segmentList) === 1
                    ? $first
                    : $segmentList,
                'label' => $this->segmentLabel($first),
                'flights' => count($segmentList),
            ];
        }

        return $result;
    }

    private function segmentLabel(array $segment): string
    {
        $airline = $segment['marketing_airline'] ?? '??';
        $flight = $segment['flight_number'] ?? '??';
        $origin = $segment['origin'] ?? '??';
        $destination = $segment['destination'] ?? '??';
        $date = explode(' ', $segment['departure_datetime'] ?? '')[0] ?? '??';
        $cls = $segment['booking_class'] ?? '?';

        return "{$airline}{$flight} {$origin}-{$destination} {$date} {$cls}";
    }

    private function buildSegmentFromContext(array $context): ?array
    {
        $segment = is_array($context['segment'] ?? null) ? $context['segment'] : [];
        $departureDateTime = $this->firstFilled($segment, $context, ['departure_datetime', 'departureDateTime']);
        $arrivalDateTime = $this->firstFilled($segment, $context, ['arrival_datetime', 'arrivalDateTime']);
        $flightNumber = $this->firstFilled($segment, $context, ['flight_number', 'flightNumber']);
        $bookingClass = $this->firstFilled($segment, $context, ['booking_class', 'res_book_desig_code', 'class']);
        $marketingAirline = $this->firstFilled($segment, $context, ['marketing_airline', 'marketingAirline', 'airline']);
        $origin = $this->firstFilled($segment, $context, ['origin', 'departure_airport', 'departureAirport']);
        $destination = $this->firstFilled($segment, $context, ['destination', 'arrival_airport', 'arrivalAirport']);

        if ($departureDateTime === null || $arrivalDateTime === null || $flightNumber === null || $bookingClass === null || $marketingAirline === null || $origin === null || $destination === null) {
            return null;
        }

        return [
            'origin' => strtoupper($origin),
            'destination' => strtoupper($destination),
            'departure_datetime' => $departureDateTime,
            'arrival_datetime' => $arrivalDateTime,
            'flight_number' => $flightNumber,
            'booking_class' => strtoupper($bookingClass),
            'marketing_airline' => strtoupper($marketingAirline),
        ];
    }

    private function firstBookingClassCode(mixed $bookingClasses): ?string
    {
        if (! is_array($bookingClasses)) {
            return null;
        }

        if (isset($bookingClasses['@attributes'])) {
            return $bookingClasses['@attributes']['ResBookDesigCode'] ?? null;
        }

        foreach ($bookingClasses as $class) {
            if (! is_array($class)) {
                continue;
            }

            if (isset($class['@attributes']['ResBookDesigCode']) && trim((string) $class['@attributes']['ResBookDesigCode']) !== '') {
                return trim((string) $class['@attributes']['ResBookDesigCode']);
            }
        }

        return null;
    }

    private function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
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
