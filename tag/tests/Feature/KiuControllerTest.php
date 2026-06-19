<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KiuControllerTest extends TestCase
{
    public function test_session_endpoint_proxies_xml_requests_to_kiu_sandbox(): void
    {
        config()->set('services.kiu.base_url', 'https://sandbox.kiu.test');
        config()->set('services.kiu.auth.mode', 'header');
        config()->set('services.kiu.auth.username', 'demo-user');
        config()->set('services.kiu.auth.password', 'demo-pass');
        config()->set('services.kiu.auth.office_id', 'CCS1');
        config()->set('services.kiu.auth.agent_sine', 'AG01');
        config()->set('services.kiu.operations.session.path', '/session/open');
        config()->set('services.kiu.operations.session.transport', 'xml');

        Http::fake([
            'https://sandbox.kiu.test/session/open' => Http::response(
                '<response><token>abc123</token><status>OK</status></response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $response = $this->withoutMiddleware()->postJson('/api/kiu/session', [
            'payload' => '<OpenSession />',
            'headers' => [
                'X-Correlation-Id' => 'req-123',
            ],
            'context' => [
                'session_label' => 'sandbox-login',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('operation', 'session')
            ->assertJsonPath('response.data.token', 'abc123')
            ->assertJsonPath('request.context.session_label', 'sandbox-login');

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/session/open')
                && str_contains($request->body(), 'OpenSession');
        });
    }

    public function test_booking_endpoint_builds_kiu_airbook_xml_from_context(): void
    {
        config()->set('services.kiu.base_url', 'https://sandbox.kiu.test');
        config()->set('services.kiu.operations.booking.transport', 'form_params');

        Http::fake([
            'https://sandbox.kiu.test' => Http::response(
                '<response><status>OK</status></response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $response = $this->withoutMiddleware()->postJson('/api/kiu/booking', [
            'context' => [
                'tour_code' => 'JUANTEST',
                'contact_email' => 'juan@perez.com',
                'contact_phone' => '123456789',
                'ticket_time_limit' => '3',
                'segment' => [
                    'origin' => 'AEP',
                    'destination' => 'COR',
                    'departureDateTime' => '2026-06-20 06:00:00',
                    'arrivalDateTime' => '2026-06-20 07:00:00',
                    'flightNumber' => '4601',
                    'res_book_desig_code' => 'Y',
                    'marketingAirline' => 'XX',
                ],
                'passengers' => [
                    [
                        'type' => 'ADT',
                        'first_name' => 'JUAN',
                        'middle_name' => 'CARLOS',
                        'last_name' => 'PEREZ',
                        'birth_date' => '1980-12-07',
                        'email' => 'juan@perez.com',
                        'phone_area_code' => '011',
                        'phone_number' => '123456789',
                        'document_id' => '123456789',
                        'document_type' => 'NI',
                        'loyalty_program_id' => 'XX',
                        'loyalty_membership_id' => '12346789',
                        'traveler_ref_number' => '01',
                    ],
                ],
                'remark' => 'TEST TOURCODE',
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://sandbox.kiu.test') {
                return false;
            }

            parse_str($request->body(), $form);
            $xml = $form['request'] ?? '';

            return str_contains($xml, '<PriceInfo><TourCode Type="N" Text="JUANTEST" /></PriceInfo>')
                && str_contains($xml, '<AirTraveler PassengerTypeCode="ADT" BirthDate="1980-12-07">')
                && str_contains($xml, '<Document DocID="123456789" DocType="NI" />')
                && str_contains($xml, '<TravelerRefNumber RPH="01" />')
                && str_contains($xml, '<Ticketing TicketTimeLimit="3" />');
        });
    }

    public function test_availability_response_includes_data_booking_segment(): void
    {
        config()->set('services.kiu.base_url', 'https://sandbox.kiu.test');
        config()->set('services.kiu.operations.availability.transport', 'xml');

        Http::fake([
            'https://sandbox.kiu.test' => Http::response(
                '<KIU_AirAvailRS EchoToken="1" TimeStamp="2026-06-05T03:47:26+00:00" Target="Production" Version="3.0" SequenceNmbr="1">'
                .'<Success/>'
                .'<OriginDestinationInformation>'
                .'<DepartureDateTime>2026-06-15</DepartureDateTime>'
                .'<OriginLocation>CCS</OriginLocation>'
                .'<DestinationLocation>MAR</DestinationLocation>'
                .'<OriginDestinationOptions>'
                .'<OriginDestinationOption>'
                .'<FlightSegment DepartureDateTime="2026-06-15 08:30:00" ArrivalDateTime="2026-06-15 09:40:00" StopQuantity="0" FlightNumber="942" JourneyDuration="01:10:00">'
                .'<DepartureAirport LocationCode="CCS" />'
                .'<ArrivalAirport LocationCode="MAR" />'
                .'<MarketingAirline CompanyShortName="QL" />'
                .'<BookingClassAvail ResBookDesigCode="D" ResBookDesigQuantity="9" RPH="1" />'
                .'</FlightSegment>'
                .'</OriginDestinationOption>'
                .'</OriginDestinationOptions>'
                .'</OriginDestinationInformation>'
                .'</KIU_AirAvailRS>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $response = $this->withoutMiddleware()->postJson('/api/kiu/availability', [
            'context' => [
                'origin' => 'CCS',
                'destination' => 'MAR',
                'departure_date' => '2026-06-15',
                'adults' => 1,
                'children' => 0,
                'infants' => 0,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('operation', 'availability')
            ->assertJsonPath('data_booking.0.label', 'QL942 CCS-MAR 2026-06-15 D')
            ->assertJsonPath('data_booking.0.flights', 1)
            ->assertJsonPath('data_booking.0.context.segment.flight_number', '942')
            ->assertJsonPath('data_booking.0.context.segment.booking_class', 'D')
            ->assertJsonPath('data_booking.0.context.segment.marketing_airline', 'QL')
            ->assertJsonPath('data_booking.0.context.adults', 1);
    }

    public function test_pricing_response_includes_data_booking_from_context_segment(): void
    {
        config()->set('services.kiu.base_url', 'https://sandbox.kiu.test');
        config()->set('services.kiu.operations.pricing.transport', 'form_params');

        Http::fake([
            'https://sandbox.kiu.test' => Http::response(
                '<KIU_AirPriceRS><Success/><PricedItineraries/></KIU_AirPriceRS>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $response = $this->withoutMiddleware()->postJson('/api/kiu/pricing', [
            'context' => [
                'currency' => 'USD',
                'adults' => 2,
                'children' => 0,
                'infants' => 1,
                'tour_code' => 'JUANTEST',
                'segment' => [
                    'origin' => 'CCS',
                    'destination' => 'MAR',
                    'departure_datetime' => '2026-07-15 08:30:00',
                    'arrival_datetime' => '2026-07-15 09:40:00',
                    'flight_number' => '942',
                    'booking_class' => 'D',
                    'marketing_airline' => 'QL',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('operation', 'pricing')
            ->assertJsonPath('data_booking.0.label', 'QL942 CCS-MAR 2026-07-15 D')
            ->assertJsonPath('data_booking.0.flights', 1)
            ->assertJsonPath('data_booking.0.context.segment.flight_number', '942')
            ->assertJsonPath('data_booking.0.context.segment.booking_class', 'D')
            ->assertJsonPath('data_booking.0.context.segment.marketing_airline', 'QL')
            ->assertJsonPath('data_booking.0.context.adults', 2)
            ->assertJsonPath('data_booking.0.context.infants', 1);
    }

    public function test_ticketing_endpoint_builds_kiu_airticket_xml_from_context(): void
    {
        config()->set('services.kiu.base_url', 'https://sandbox.kiu.test');
        config()->set('services.kiu.operations.ticketing.transport', 'form_params');

        Http::fake([
            'https://sandbox.kiu.test' => Http::response(
                '<response><status>OK</status></response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $response = $this->withoutMiddleware()->postJson('/api/kiu/ticketing', [
            'context' => [
                'reservation_code' => 'ABC123',
                'currency' => 'USD',
                'form_of_payment' => 'CASH',
                'endorsement' => 'NO ENDOSABLE',
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://sandbox.kiu.test') {
                return false;
            }

            parse_str($request->body(), $form);
            $xml = $form['request'] ?? '';

            return str_contains($xml, '<KIU_AirDemandTicketRQ')
                && str_contains($xml, '<BookingReferenceID Type="1" ID="ABC123" />')
                && str_contains($xml, '<FormOfPayment Type="CASH" />')
                && str_contains($xml, '<Endorsement><Text>NO ENDOSABLE</Text></Endorsement>')
                && str_contains($xml, 'TicketingControl="COMMIT"');
        });
    }

    public function test_ticketing_validate_only_mode_sets_validate_control(): void
    {
        config()->set('services.kiu.base_url', 'https://sandbox.kiu.test');
        config()->set('services.kiu.operations.ticketing.transport', 'form_params');

        Http::fake([
            'https://sandbox.kiu.test' => Http::response(
                '<response><status>OK</status></response>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $this->withoutMiddleware()->postJson('/api/kiu/ticketing', [
            'context' => [
                'reservation_code' => 'XYZ999',
                'validate_only' => true,
            ],
        ]);

        Http::assertSent(function (Request $request): bool {
            parse_str($request->body(), $form);
            $xml = $form['request'] ?? '';

            return str_contains($xml, '<KIU_AirDemandTicketRQ')
                && str_contains($xml, 'TicketingControl="VALIDATE"');
        });
    }

    public function test_post_sale_endpoint_returns_upstream_error_status(): void
    {
        config()->set('services.kiu.base_url', 'https://sandbox.kiu.test');
        config()->set('services.kiu.operations.post_sale.path', '/booking/post-sale');

        Http::fake([
            'https://sandbox.kiu.test/booking/post-sale' => Http::response(
                '<error><message>PNR not found</message></error>',
                404,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $response = $this->withoutMiddleware()->postJson('/api/kiu/post-sale', [
            'payload' => '<CancelReservation />',
            'context' => [
                'action' => 'cancel',
                'reservation_code' => 'ABC123',
            ],
        ]);

        $response
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('response.data.message', 'PNR not found');
    }
}
