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
            return $request->url() === 'https://sandbox.kiu.test/session/open'
                && $request->hasHeader('X-KIU-Username', 'demo-user')
                && $request->hasHeader('X-KIU-Password', 'demo-pass')
                && $request->body() === '<OpenSession />';
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