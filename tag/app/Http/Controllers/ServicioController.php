<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServicioResource;
use App\Models\OrdenCompra;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServicioController extends Controller
{
    /**
     * Listar todos los servicios
     *
     * Devuelve el listado de servicios activos registrados en el sistema.
     */
    public function index(Request $request)
    {
        $query = Servicio::query();

        // Soporte para filtros
        if ($request->has('id_cotizacion')) {
            $query->where('id_cotizacion', $request->id_cotizacion);
        }

        if ($request->has('id_proveedor')) {
            $query->where('id_proveedor', $request->id_proveedor);
        }

        // Soporte para relaciones
        if ($request->has('include')) {
            $allowed = ['tipoServicio', 'proveedor', 'tasaCambio'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (! empty($includes)) {
                $query->with(collect($includes)->mapWithKeys(function ($include) {
                    if ($include === 'proveedor' || $include === 'tipoServicio') {
                        return [$include => fn ($q) => $q->withTrashed()];
                    }

                    return [$include => fn ($q) => $q];
                })->toArray());
            }
        }

        return ServicioResource::collection($query->orderBy('id')->get());
    }

    /**
     * Crear un nuevo servicio
     *
     * @bodyParam id_tipo_servicio int required ID del tipo de servicio. Ejemplo: 1
     * @bodyParam id_proveedor int required ID del proveedor del servicio. Ejemplo: 1
     * @bodyParam descripcion string optional Detalles de qué incluye el servicio.
     * @bodyParam costo number required Precio de costo interno del servicio. Ejemplo: 150.00
     * @bodyParam monto_gravable number required Monto sobre el cual se aplica IVA. Ejemplo: 150.00
     * @bodyParam monto_no_sujeto number required Monto libre de impuestos. Ejemplo: 0.00
     * @bodyParam iva_establecido number Porcentaje fijo de IVA que recaerá en el monto gravable. Ejemplo: 16.00
     * @bodyParam id_tasa_cambio int required ID de la tasa de cambio aplicada. Ejemplo: 1
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cotizacion' => ['required', Rule::exists('cotizaciones', 'id')->whereNull('deleted_at')],
            'id_tipo_servicio' => ['required', Rule::exists('tipo_servicio', 'id')->whereNull('deleted_at')],
            'id_proveedor' => ['required', Rule::exists('proveedores', 'id')->whereNull('deleted_at')],
            'descripcion' => ['nullable', 'string'],
            'costo' => ['required', 'numeric', 'min:0'],
            'monto_gravable' => ['required', 'numeric', 'min:0'],
            'monto_no_sujeto' => ['required', 'numeric', 'min:0'],
            'iva_establecido' => ['nullable', 'numeric', 'min:0'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
        ]);

        // La totalización (Base Imponible + Impuestos) es calculada en tiempo real
        // e interceptada por App\Observers\ServicioObserver antes de tocar DB.
        $servicio = Servicio::create($data);

        // Recalcular la orden de compra si existe
        $this->recalcularOrdenCompraPorCotizacion($servicio->id_cotizacion);

        return new ServicioResource($servicio->load(['tipoServicio', 'proveedor', 'tasaCambio']));
    }

    /**
     * Obtener un servicio específico
     */
    public function show(Servicio $servicio)
    {
        return new ServicioResource($servicio->load(['tipoServicio', 'proveedor', 'tasaCambio']));
    }

    /**
     * Actualizar un servicio existente
     */
    public function update(Request $request, Servicio $servicio)
    {
        $data = $request->validate([
            'id_cotizacion' => ['sometimes', 'required', Rule::exists('cotizaciones', 'id')->whereNull('deleted_at')],
            'id_tipo_servicio' => ['sometimes', 'required', Rule::exists('tipo_servicio', 'id')->whereNull('deleted_at')],
            'id_proveedor' => ['sometimes', 'required', Rule::exists('proveedores', 'id')->whereNull('deleted_at')],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'costo' => ['sometimes', 'required', 'numeric', 'min:0'],
            'monto_gravable' => ['sometimes', 'required', 'numeric', 'min:0'],
            'monto_no_sujeto' => ['sometimes', 'required', 'numeric', 'min:0'],
            'iva_establecido' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
        ]);

        $idCotizacionAnterior = $servicio->id_cotizacion;

        // La sumatoria real es manejada en el ServicioObserver de manera autónoma.
        $servicio->update($data);

        // Recalcular órdenes de compra involucradas
        $this->recalcularOrdenCompraPorCotizacion($idCotizacionAnterior);
        if ($servicio->id_cotizacion !== $idCotizacionAnterior) {
            $this->recalcularOrdenCompraPorCotizacion($servicio->id_cotizacion);
        }

        return new ServicioResource($servicio->fresh()->load(['tipoServicio', 'proveedor', 'tasaCambio']));
    }

    /**
     * Eliminar un servicio
     * Usa SoftDeletes nativo de Eloquent.
     */
    public function destroy(Servicio $servicio)
    {
        $idCotizacion = $servicio->id_cotizacion;
        $servicio->delete();

        // Recalcular la orden de compra
        $this->recalcularOrdenCompraPorCotizacion($idCotizacion);

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }

    private function recalcularOrdenCompraPorCotizacion(int $idCotizacion): void
    {
        $ordenCompra = OrdenCompra::where('id_cotizacion', $idCotizacion)->first();
        $ordenCompra?->recalcularMontoTotal();
    }
}
