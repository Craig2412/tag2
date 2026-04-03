<?php

namespace App\Http\Controllers;

use App\Services\Kiu\KiuClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class KiuController extends Controller
{
    public function __construct(private readonly KiuClient $kiuClient)
    {
    }

    public function session(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.session_label' => ['sometimes', 'string', 'max:100'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->session($validated));
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.origin' => ['sometimes', 'string', 'size:3'],
            'context.destination' => ['sometimes', 'string', 'size:3'],
            'context.departure_date' => ['sometimes', 'date'],
            'context.return_date' => ['sometimes', 'date'],
            'context.adults' => ['sometimes', 'integer', 'min:1'],
            'context.children' => ['sometimes', 'integer', 'min:0'],
            'context.infants' => ['sometimes', 'integer', 'min:0'],
            'context.cabin' => ['sometimes', 'string', 'max:20'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->availability($validated));
    }

    public function pricing(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.reservation_code' => ['sometimes', 'string', 'max:30'],
            'context.currency' => ['sometimes', 'string', 'size:3'],
            'context.fare_basis' => ['sometimes', 'string', 'max:30'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->pricing($validated));
    }

    public function booking(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.passengers' => ['sometimes', 'array'],
            'context.passengers.*.first_name' => ['sometimes', 'string', 'max:100'],
            'context.passengers.*.last_name' => ['sometimes', 'string', 'max:100'],
            'context.passengers.*.type' => ['sometimes', 'string', 'max:10'],
            'context.contact_email' => ['sometimes', 'email'],
            'context.contact_phone' => ['sometimes', 'string', 'max:30'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->booking($validated));
    }

    public function ticketing(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.reservation_code' => ['sometimes', 'string', 'max:30'],
            'context.payment_reference' => ['sometimes', 'string', 'max:60'],
            'context.validate_only' => ['sometimes', 'boolean'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->ticketing($validated));
    }

    public function postSale(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.action' => ['sometimes', 'in:change,cancel,void,refund,reissue'],
            'context.reservation_code' => ['sometimes', 'string', 'max:30'],
            'context.ticket_number' => ['sometimes', 'string', 'max:30'],
            'context.reason' => ['sometimes', 'string', 'max:500'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->postSale($validated));
    }

    private function baseRules(): array
    {
        return [
            'payload' => ['required'],
            'headers' => ['sometimes', 'array'],
            'headers.*' => ['nullable', 'string'],
            'query' => ['sometimes', 'array'],
            'query.*' => ['nullable', 'string'],
            'credentials' => ['sometimes', 'array'],
            'credentials.username' => ['sometimes', 'string'],
            'credentials.password' => ['sometimes', 'string'],
            'credentials.office_id' => ['sometimes', 'string'],
            'credentials.agent_sine' => ['sometimes', 'string'],
            'soap_action' => ['sometimes', 'string'],
            'transport' => ['sometimes', 'in:xml,json,form_params'],
            'timeout' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'verify' => ['sometimes', 'boolean'],
            'context' => ['sometimes', 'array'],
        ];
    }

    private function dispatch(callable $callback): JsonResponse
    {
        try {
            $result = $callback();
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 502);
        }

        $status = (int) ($result['response']['status'] ?? 200);
        $status = $status >= 100 && $status <= 599 ? $status : 200;

        return response()->json($result, $status);
    }
}