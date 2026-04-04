<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    /**
     * Listar todas las cotizaciones
     *
     * Devuelve todas las cotizaciones activas junto con su orden de compra asociada.
     */
    public function index()
    {
        // Lista las cotizaciones activas y su orden de compra asociada.
        $items = Cotizacion::where('borrado_logico', false)
            ->orderBy('id')
            ->with('ordenCompra')
            ->get()
            ->values();

        return response()->json($items);
    }

    /**
     * Crear una nueva cotización
     *
     * Registra una cotización en estatus "por confirmar". Luego puede confirmarse con `PUT /api/cotizaciones/{id}`.
     *
     * @bodyParam id_atencion int required ID de la atención asociada. Ejemplo: 3
     * @bodyParam id_tipo_cotizacion int required ID del tipo de cotización. Ejemplo: 1
     * @bodyParam cant_adultos int required Cantidad de adultos. Ejemplo: 2
     * @bodyParam cant_menores int required Cantidad de menores. Ejemplo: 1
     * @bodyParam cant_viejos int required Cantidad de adultos mayores. Ejemplo: 0
     * @bodyParam id_tasa_asignada int required ID de la tasa de gestión asignada. Ejemplo: 1
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente. Ejemplo: false
     */
    public function store(Request $request)
    {
        // Crea una cotizacion en estado inicial por confirmar.
        $data = $request->validate([
            'id_atencion' => ['required', 'exists:atenciones,id'],
            'id_tipo_cotizacion' => ['required', 'exists:tipos_cotizaciones,id'],
            'cant_adultos' => ['required', 'integer', 'min:0'],
            'cant_menores' => ['required', 'integer', 'min:0'],
            'cant_viejos' => ['required', 'integer', 'min:0'],
            'id_tasa_asignada' => ['required', 'exists:tasas,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $estatus = Estatus::firstOrCreate(['estatus' => 'por confirmar']);

        $data['estatus'] = $estatus->id;
        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $item = Cotizacion::create($data);
        $item->load(['atencion', 'tipoCotizacion', 'tasaAsignada', 'estatus', 'ordenCompra']);

        return response()->json($item, 201);
    }

    /**
     * Obtener una cotización específica
     *
     * Devuelve los datos de una cotización junto con su orden de compra.
     */
    public function show(Cotizacion $cotizacion)
    {
        // Muestra una cotizacion si no esta marcada como borrada.
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($cotizacion->load('ordenCompra'));
    }

    /**
     * Actualizar una cotización existente
     *
     * Modifica una cotización activa. Al confirmarla (estatus = confirmado), se crea o actualiza automáticamente la orden de compra.
     *
     * @bodyParam id_atencion int ID de la atención asociada.
     * @bodyParam id_tipo_cotizacion int ID del tipo de cotización.
     * @bodyParam cant_adultos int Cantidad de adultos.
     * @bodyParam cant_menores int Cantidad de menores.
     * @bodyParam cant_viejos int Cantidad de adultos mayores.
     * @bodyParam id_tasa_asignada int ID de la tasa de gestión.
     * @bodyParam estatus int ID del estatus (por confirmar / confirmado).
     * @bodyParam id_tasa_cambio int ID de la tasa de cambio (requerido al confirmar la cotización).
     */
    public function update(Request $request, Cotizacion $cotizacion)
    {
        // Actualiza una cotizacion y crea/actualiza orden de compra al confirmar.
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $data = $request->validate([
            'id_atencion' => ['sometimes', 'required', 'exists:atenciones,id'],
            'id_tipo_cotizacion' => ['sometimes', 'required', 'exists:tipos_cotizaciones,id'],
            'cant_adultos' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_menores' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_viejos' => ['sometimes', 'required', 'integer', 'min:0'],
            'id_tasa_asignada' => ['sometimes', 'required', 'exists:tasas,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $estatusPorConfirmar = Estatus::firstOrCreate(['estatus' => 'por confirmar']);
        $estatusConfirmado = Estatus::firstOrCreate(['estatus' => 'confirmado']);

        if (isset($data['estatus']) && !in_array($data['estatus'], [$estatusPorConfirmar->id, $estatusConfirmado->id], true)) {
            return response()->json(['message' => 'La cotizacion solo admite estatus por confirmar o confirmado'], 422);
        }

        $estatusActual = (int) $cotizacion->getRawOriginal('estatus');
        $estatusNuevo = $data['estatus'] ?? $estatusActual;
        $idTasaCambio = $data['id_tasa_cambio'] ?? null;
        unset($data['id_tasa_cambio']);

        if ($estatusActual === $estatusConfirmado->id && $estatusNuevo === $estatusPorConfirmar->id) {
            return response()->json(['message' => 'Una cotizacion confirmada no puede volver a por confirmar'], 422);
        }

        if ($estatusActual !== $estatusConfirmado->id && $estatusNuevo === $estatusConfirmado->id && !$idTasaCambio) {
            return response()->json(['message' => 'Debe indicar id_tasa_cambio para confirmar la cotizacion'], 422);
        }

        $cotizacion->update($data);

        if ($estatusNuevo === $estatusConfirmado->id) {
            $estatusPendiente = Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);
            $idTasaCambio = $idTasaCambio
                ?? OrdenCompra::where('id_cotizacion', $cotizacion->id)->value('id_tasa_cambio');

            if (!$idTasaCambio) {
                return response()->json(['message' => 'Debe indicar id_tasa_cambio para confirmar la cotizacion'], 422);
            }

            OrdenCompra::updateOrCreate(
                ['id_cotizacion' => $cotizacion->id],
                [
                    'id_tasa_cambio' => $idTasaCambio,
                    'estatus' => $estatusPendiente->id,
                    'monto_total' => 0,
                ]
            );

            $ordenCompra = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
            $ordenCompra?->recalcularMontoTotal();
        }

        return response()->json($cotizacion->fresh()->load(['atencion', 'tipoCotizacion', 'tasaAsignada', 'estatus', 'ordenCompra']));
    }

    /**
     * Eliminar una cotización
     *
     * Realiza una eliminación lógica de la cotización (no se borra físicamente de la base de datos).
     */
    public function destroy(Cotizacion $cotizacion)
    {
        // Marca la cotizacion como borrada de forma logica.
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Ya estaba eliminada']);
        }

        $cotizacion->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
