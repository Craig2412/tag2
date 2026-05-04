<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\OrdenCompra;
use App\Models\Servicio;
use App\Http\Resources\CotizacionResource;
use App\Events\CotizacionEstatusActualizado;
use App\Services\EstatusResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return CotizacionResource::collection($items);
    }

    /**
     * Crear una nueva cotización
     *
     * Permite crear una cotización junto con sus servicios asociados de forma atómica.
     *
     * @bodyParam id_atencion int required ID de la atención asociada. Ejemplo: 1
     * @bodyParam id_tipo_cotizacion int required ID del tipo de cotización. Ejemplo: 1
     * @bodyParam cant_adultos int required Cantidad de adultos. Ejemplo: 2
     * @bodyParam cant_menores int required Cantidad de menores. Ejemplo: 0
     * @bodyParam cant_viejos int required Cantidad de adultos mayores. Ejemplo: 0
     * @bodyParam id_tasa_cambio int required ID de la tasa de cambio congelada (Historial Tasa). Ejemplo: 1
     * @bodyParam fecha_vencimiento date required Fecha de caducidad de la proforma. Ejemplo: 2026-04-30
     * @bodyParam servicios object[] Lista de servicios a crear.
     * @bodyParam servicios[].id_tipo_servicio int required ID del tipo de servicio. Ejemplo: 1
     * @bodyParam servicios[].id_proveedor int required ID del proveedor. Ejemplo: 1
     * @bodyParam servicios[].descripcion string Detalles del servicio. Ejemplo: Tour guiado por la ciudad
     * @bodyParam servicios[].costo number required Costo para el proveedor. Ejemplo: 100.00
     * @bodyParam servicios[].monto_gravable number required Base imponible venta. Ejemplo: 120.00
     * @bodyParam servicios[].monto_no_sujeto number required Monto no sujeto a IVA venta. Ejemplo: 0.00
     * @bodyParam servicios[].iva_establecido number Porcentaje de IVA para venta. Ejemplo: 16.00
     * @bodyParam servicios[].id_tasa_cambio int required ID de la tasa aplicada al servicio. Ejemplo: 1
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
            'servicios' => ['sometimes', 'array'],
            'servicios.*.id_tipo_servicio' => ['required_with:servicios', 'exists:tipo_servicio,id'],
            'servicios.*.id_proveedor' => ['required_with:servicios', 'exists:proveedores,id'],
            'servicios.*.descripcion' => ['nullable', 'string'],
            'servicios.*.costo' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.monto_gravable' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.monto_no_sujeto' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.iva_establecido' => ['nullable', 'numeric', 'min:0'],
            'servicios.*.id_tasa_cambio' => ['required_with:servicios', 'exists:tasas_cambio,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $data['estatus'] = EstatusResolver::idOrFail('por confirmar');

            $item = Cotizacion::create($data);

            if (isset($data['servicios'])) {
                foreach ($data['servicios'] as $servicioData) {
                    $servicioData['id_cotizacion'] = $item->id;
                    Servicio::create($servicioData);
                }
            }

            // Registrar estatus inicial en el historial
            event(new CotizacionEstatusActualizado(
                cotizacion: $item,
                estatusAnterior: null,
                estatusNuevo: $item->estatus,
                comentario: 'Cotización creada',
            ));

            return new CotizacionResource($item->load(['atencion', 'tipoCotizacion', 'tasaCambio', 'estatus', 'servicios']));
        });
    }

    /**
     * Obtener una cotización específica
     */
    public function show(Cotizacion $cotizacion)
    {
        return new CotizacionResource($cotizacion->load(['ordenCompra', 'tasaCambio']));
    }

    /**
     * Actualizar una cotización existente
     *
     * Modifica una cotización activa y sincroniza sus servicios. Al confirmarla, se genera la Orden de Compra automáticamente.
     *
     * @bodyParam id_atencion int ID de la atención. Ejemplo: 1
     * @bodyParam id_tipo_cotizacion int ID del tipo de cotización. Ejemplo: 1
     * @bodyParam cant_adultos int Cantidad de adultos. Ejemplo: 2
     * @bodyParam cant_menores int Cantidad de menores. Ejemplo: 0
     * @bodyParam cant_viejos int Cantidad de adultos mayores. Ejemplo: 0
     * @bodyParam id_tasa_cambio int ID de la tasa de cambio aplicada. Ejemplo: 1
     * @bodyParam fecha_vencimiento date Fecha de vencimiento. Ejemplo: 2026-04-30
     * @bodyParam estatus int ID del nuevo estatus. Ejemplo: 1
     * @bodyParam servicios object[] Lista completa de servicios (sincronización).
     * @bodyParam servicios[].id int ID del servicio si se va a actualizar. Ejemplo: 1
     * @bodyParam servicios[].id_tipo_servicio int required ID del tipo de servicio. Ejemplo: 1
     * @bodyParam servicios[].id_proveedor int required ID del proveedor. Ejemplo: 1
     * @bodyParam servicios[].costo number required Costo para el proveedor. Ejemplo: 100.00
     * @bodyParam servicios[].monto_gravable number required Base imponible venta. Ejemplo: 120.00
     * @bodyParam servicios[].monto_no_sujeto number required Monto no sujeto a IVA venta. Ejemplo: 0.00
     * @bodyParam servicios[].id_tasa_cambio int required ID de la tasa aplicada al servicio. Ejemplo: 1
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
            'servicios' => ['sometimes', 'array'],
            'servicios.*.id' => ['sometimes', 'exists:servicios,id'],
            'servicios.*.id_tipo_servicio' => ['required_with:servicios', 'exists:tipo_servicio,id'],
            'servicios.*.id_proveedor' => ['required_with:servicios', 'exists:proveedores,id'],
            'servicios.*.costo' => ['required_with:servicios', 'numeric' , 'min:0'],
            'servicios.*.monto_gravable' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.monto_no_sujeto' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.id_tasa_cambio' => ['required_with:servicios', 'exists:tasas_cambio,id'],
        ]);

        $idPorConfirmar = EstatusResolver::idOrFail('por confirmar');
        $idConfirmado   = EstatusResolver::idOrFail('confirmado');

        if (isset($data['estatus']) && !in_array($data['estatus'], [$idPorConfirmar, $idConfirmado], true)) {
            return response()->json(['message' => 'La cotización solo admite estatus por confirmar o confirmado'], 422);
        }

        return DB::transaction(function () use ($data, $cotizacion, $idConfirmado, $idPorConfirmar) {
            $estatusActual = (int) $cotizacion->getRawOriginal('estatus');
            $estatusNuevo = $data['estatus'] ?? $estatusActual;

            if ($estatusActual === $idConfirmado && $estatusNuevo === $idPorConfirmar) {
                // No lanzamos excepcion aqui para el transaction, sino respuesta directa
                abort(422, 'Una cotización confirmada no puede devolverse a por confirmar');
            }

            $cotizacion->update($data);

            // --- Lógica de Sincronización de Servicios ---
            if (isset($data['servicios'])) {
                $serviciosRecibidosIds = collect($data['servicios'])->pluck('id')->filter()->toArray();

                // 1. Eliminar servicios que no están en la lista
                $cotizacion->servicios()->whereNotIn('id', $serviciosRecibidosIds)->delete();

                // 2. Crear o Actualizar
                foreach ($data['servicios'] as $servicioData) {
                    if (isset($servicioData['id'])) {
                        $servicio = Servicio::find($servicioData['id']);
                        $servicio?->update($servicioData);
                    } else {
                        $servicioData['id_cotizacion'] = $cotizacion->id;
                        Servicio::create($servicioData);
                    }
                }
            }

            // --- Registro de Historial ---
            if (isset($data['estatus']) && $data['estatus'] != $estatusActual) {
                event(new CotizacionEstatusActualizado(
                    cotizacion: $cotizacion,
                    estatusAnterior: $estatusActual,
                    estatusNuevo: $data['estatus'],
                ));
            }

            // --- MÁQUINA DE ESTADOS / DISPARADOR DE ORDEN COMPRA ---
            if ($estatusNuevo === $idConfirmado) {
                $estatusOperativo = EstatusResolver::id('Pendiente Procesamiento');
                $estadoFinancieroPendiente = \App\Models\EstadoFinanciero::where('slug', 'pendiente')->first()?->id ?? 1;
                
                OrdenCompra::updateOrCreate(
                    ['id_cotizacion' => $cotizacion->id],
                    [
                        'estatus'              => $estatusOperativo,
                        'id_estado_financiero' => $estadoFinancieroPendiente,
                        'monto_total'          => 0,
                    ]
                );
            }

            // Siempre recalcular si hubo cambios de servicios u OC existe
            $ordenCompra = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
            $ordenCompra?->recalcularMontoTotal();

            return new CotizacionResource($cotizacion->fresh()->load(['atencion', 'tipoCotizacion', 'tasaCambio', 'estatus', 'ordenCompra', 'servicios']));
        });
    }

    /**
     * Eliminar una cotización
     */
    public function destroy(Cotizacion $cotizacion)
    {
        $cotizacion->delete();
        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
