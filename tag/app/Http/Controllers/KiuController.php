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

    /**
     * Abrir sesión en Kiu
     *
     * Inicia una sesión SOAP con el sistema GDS de Kiu.
     *
     * @bodyParam payload string required El payload XML SOAP de la solicitud.
     * @bodyParam credentials object Credenciales de Kiu (username, password, office_id).
     * @bodyParam context object Datos de contexto adicionales.
     * @bodyParam context.session_label string Etiqueta para identificar la sesión. Ejemplo: MI-SESION-1
     */
    public function session(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.session_label' => ['sometimes', 'string', 'max:100'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->session($validated));
    }

    /**
     * Consultar disponibilidad de vuelos en Kiu
     *
     * Obtiene la disponibilidad de vuelos y tarifas para una ruta y fecha dadas.
     *
     * @bodyParam payload string required El payload XML SOAP de la solicitud.
     * @bodyParam context object Datos de contexto adicionales.
     * @bodyParam context.origin string Código IATA del aeropuerto de origen. Ejemplo: CCS
     * @bodyParam context.destination string Código IATA del aeropuerto de destino. Ejemplo: MIA
     * @bodyParam context.departure_date date Fecha de salida. Ejemplo: 2026-05-20
     * @bodyParam context.adults int Cantidad de adultos. Ejemplo: 1
     */
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

    /**
     * Calcular precio de vuelo o reserva en Kiu
     *
     * Obtiene el precio detallado de una reserva o selección de vuelos.
     *
     * @bodyParam payload string required El payload XML SOAP de la solicitud.
     * @bodyParam context object Datos de contexto adicionales.
     * @bodyParam context.reservation_code string Código PNR de la reserva en Kiu. Ejemplo: ABCD12
     * @bodyParam context.currency string Código de moneda (ej. USD). Ejemplo: USD
     */
    public function pricing(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.reservation_code' => ['sometimes', 'string', 'max:30'],
            'context.currency' => ['sometimes', 'string', 'size:3'],
            'context.fare_basis' => ['sometimes', 'string', 'max:30'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->pricing($validated));
    }

    /**
     * Crear una reserva en Kiu
     *
     * Genera un PNR con los datos de pasajeros y contacto provistos.
     *
     * @bodyParam payload string required El payload XML SOAP de la solicitud.
     * @bodyParam context object Datos de pasajeros y contacto.
     * @bodyParam context.passengers object[] Lista de pasajeros de la reserva.
     * @bodyParam context.contact_email email Correo electrónico de contacto para la reserva.
     */
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

    /**
     * Emitir boleto en Kiu
     *
     * Realiza la emisión del boleto electrónico para una reserva confirmada y pagada.
     *
     * @bodyParam payload string required El payload XML SOAP de la solicitud.
     * @bodyParam context object Detalles de pago y reserva.
     * @bodyParam context.reservation_code string El PNR a ticketear. Ejemplo: ABCD12
     * @bodyParam context.payment_reference string El voucher o referencia de pago. Ejemplo: VOUCHER-999
     */
    public function ticketing(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->baseRules(), [
            'context.reservation_code' => ['sometimes', 'string', 'max:30'],
            'context.payment_reference' => ['sometimes', 'string', 'max:60'],
            'context.validate_only' => ['sometimes', 'boolean'],
        ]));

        return $this->dispatch(fn () => $this->kiuClient->ticketing($validated));
    }

    /**
     * Operaciones post-venta en Kiu (Anulación / Reembolso / Cancelación)
     *
     * Ejecuta una acción post-venta sobre una reserva o boleto emitido.
     *
     * @bodyParam payload string required El payload XML SOAP de la solicitud.
     * @bodyParam context object Detalles de la acción a ejecutar.
     * @bodyParam context.action string required Acción post-venta (void, refund, cancel, change, reissue). Ejemplo: void
     * @bodyParam context.reservation_code string Código PNR de la reserva.
     * @bodyParam context.ticket_number string Número del boleto emitido.
     */
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