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
     * Devuelve todas las cotizaciones activas mediante SoftDeletes.
     */
    public function index()
    {
        $items = Cotizacion::orderBy('id')
            ->with(['ordenCompra', 'tasaCambio', 'tipoCotizacion', 'atencion'])
            ->get()
            ->values();

        return response()->json($items);
    }

    /**
     * Crear una nueva cotización
     *
     * @bodyParam id_atencion int required ID de la atención asociada. Ejemplo: 3
     * @bodyParam id_tipo_cotizacion int required ID del tipo de cotización. Ejemplo: 1
     * @bodyParam cant_adultos int required Cantidad de adultos. Ejemplo: 2
     * @bodyParam cant_menores int required Cantidad de menores. Ejemplo: 1
     * @bodyParam cant_viejos int required Cantidad de adultos mayores. Ejemplo: 0
     * @bodyParam id_tasa_cambio int required ID de la tasa de cambio congelada (Historial Tasa). Ejemplo: 12
     * @bodyParam fecha_vencimiento date required Fecha de caducidad de la proforma. Ejemplo: 2026-04-10
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_atencion' => ['required', 'exists:atenciones,id'],
            'id_tipo_cotizacion' => ['required', 'exists:tipos_cotizaciones,id'],
            'cant_adultos' => ['required', 'integer', 'min:0'],
            'cant_menores' => ['required', 'integer', 'min:0'],
            'cant_viejos' => ['required', 'integer', 'min:0'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
            'fecha_vencimiento' => ['required', 'date'],
        ]);

        $estatus = Estatus::firstOrCreate(['estatus' => 'por confirmar']);
        $data['estatus'] = $estatus->id;

        $item = Cotizacion::create($data);
        $item->load(['atencion', 'tipoCotizacion', 'tasaCambio', 'estatus', 'ordenCompra']);

        return response()->json($item, 201);
    }

    /**
     * Obtener una cotización específica
     */
    public function show(Cotizacion $cotizacion)
    {
        return response()->json($cotizacion->load(['ordenCompra', 'tasaCambio']));
    }

    /**
     * Actualizar una cotización existente
     *
     * Modifica una cotización activa. Al confirmarla (estatus = confirmado), se genera la Orden de Compra automáticamente (Estado Financiero: POR_PAGAR).
     */
    public function update(Request $request, Cotizacion $cotizacion)
    {
        $data = $request->validate([
            'id_atencion' => ['sometimes', 'required', 'exists:atenciones,id'],
            'id_tipo_cotizacion' => ['sometimes', 'required', 'exists:tipos_cotizaciones,id'],
            'cant_adultos' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_menores' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_viejos' => ['sometimes', 'required', 'integer', 'min:0'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'fecha_vencimiento' => ['sometimes', 'required', 'date'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
        ]);

        $estatusPorConfirmar = Estatus::firstOrCreate(['estatus' => 'por confirmar']);
        $estatusConfirmado = Estatus::firstOrCreate(['estatus' => 'confirmado']);

        if (isset($data['estatus']) && !in_array($data['estatus'], [$estatusPorConfirmar->id, $estatusConfirmado->id], true)) {
            return response()->json(['message' => 'La cotización solo admite estatus por confirmar o confirmado'], 422);
        }

        $estatusActual = (int) $cotizacion->getRawOriginal('estatus');
        $estatusNuevo = $data['estatus'] ?? $estatusActual;

        if ($estatusActual === $estatusConfirmado->id && $estatusNuevo === $estatusPorConfirmar->id) {
            return response()->json(['message' => 'Una cotización confirmada no puede devolverse a por confirmar'], 422);
        }

        $cotizacion->update($data);

        // Si cambió el estatus, registrar historial (Asumiendo que existe el modelo)
        if (isset($data['estatus']) && $data['estatus'] != $estatusActual) {
            \App\Models\CotizacionHistorial::create([
                'cotizacion_id' => $cotizacion->id,
                'estatus_anterior' => $estatusActual,
                'estatus_nuevo' => $data['estatus'],
                'usuario_id' => auth()->id(),
                'comentario' => 'Cambio de estatus desde API',
            ]);
        }

        // --- MÁQUINA DE ESTADOS / DISPARADOR DE ORDEN COMPRA ---
        if ($estatusNuevo === $estatusConfirmado->id) {
            $estatusOperativo = Estatus::firstOrCreate(['estatus' => 'Pendiente Procesamiento']);
            
            OrdenCompra::updateOrCreate(
                ['id_cotizacion' => $cotizacion->id],
                [
                    'estatus' => $estatusOperativo->id,
                    'estado_financiero' => 'POR_PAGAR',
                    'monto_total' => 0, // Recalculado por otros métodos u Observers si se adjuntan servicios después
                ]
            );

            $ordenCompra = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
            $ordenCompra?->recalcularMontoTotal();
        }

        return response()->json($cotizacion->fresh()->load(['atencion', 'tipoCotizacion', 'tasaCambio', 'estatus', 'ordenCompra']));
    }

    /**
     * Eliminar una cotización
     */
    public function destroy(Cotizacion $cotizacion)
    {
        $cotizacion->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
