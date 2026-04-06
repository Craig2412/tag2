<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use Illuminate\Http\Request;

class CotizacionController extends Controller
{
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

        return response()->json($item, 201);
    }

    public function show(Cotizacion $cotizacion)
    {
        // Muestra una cotizacion si no esta marcada como borrada.
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($cotizacion->load('ordenCompra'));
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        // Actualiza una cotizacion y crea/actualiza orden de compra al confirmar.
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
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

        // Si cambió el estatus, registrar en historial
        if (isset($data['estatus']) && $data['estatus'] != $estatusActual) {
            \App\Models\CotizacionHistorial::create([
                'cotizacion_id' => $cotizacion->id,
                'estatus_anterior' => $estatusActual,
                'estatus_nuevo' => $data['estatus'],
                'usuario_id' => auth()->id(),
                'comentario' => 'Cambio de estatus desde API',
            ]);
        }

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

        return response()->json($cotizacion->fresh()->load('ordenCompra'));
    }

    public function destroy(Cotizacion $cotizacion)
    {
        // Marca la cotizacion como borrada de forma logica.
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $cotizacion->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }
}
