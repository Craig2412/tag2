<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\ServicioCotizacion;
use Illuminate\Http\Request;

class ServicioCotizacionController extends Controller
{
    /**
     * Listar todas las vinculaciones servicio-cotización
     *
     * Devuelve el listado de servicios asociados a cada cotización del sistema.
     */
    public function index()
    {
        // Lista las relaciones servicio-cotización y las devuelve en JSON.
        return response()->json(ServicioCotizacion::orderBy('id')->get());
    }

    /**
     * Asociar un servicio a una cotización
     * 
     * @bodyParam id_servicio int required ID del servicio que se asocia. Ejemplo: 1
     * @bodyParam id_cotizacion int required ID de la cotización a la que pertenece. Ejemplo: 1
     */
    public function store(Request $request)
    {
        // Asocia un servicio con una cotización y recalcula los montos de la orden de compra si existe.
        $data = $request->validate([
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_cotizacion' => ['required', 'exists:cotizaciones,id'],
        ]);

        $item = ServicioCotizacion::create($data);
        $this->recalcularOrdenCompraPorCotizacion($item->id_cotizacion);

        return response()->json($item, 201);
    }

    /**
     * Obtener una vinculación servicio-cotización específica
     *
     * Devuelve los datos de una vinculación por su ID.
     */
    public function show(ServicioCotizacion $servicioCotizacion)
    {
        // Muestra una relación servicio-cotización por id.
        return response()->json($servicioCotizacion);
    }

    /**
     * Actualizar vinculación servicio-cotización
     * 
     * @bodyParam id_servicio int ID del servicio.
     * @bodyParam id_cotizacion int ID de la cotización.
     */
    public function update(Request $request, ServicioCotizacion $servicioCotizacion)
    {
        // Actualiza la relación entre servicio y cotización, recalculando montos.
        $data = $request->validate([
            'id_servicio' => ['sometimes', 'required', 'exists:servicios,id'],
            'id_cotizacion' => ['sometimes', 'required', 'exists:cotizaciones,id'],
        ]);

        $idCotizacionAnterior = $servicioCotizacion->id_cotizacion;

        $servicioCotizacion->update($data);
        $this->recalcularOrdenCompraPorCotizacion($idCotizacionAnterior);
        $this->recalcularOrdenCompraPorCotizacion($servicioCotizacion->id_cotizacion);

        return response()->json($servicioCotizacion);
    }

    /**
     * Eliminar vinculación servicio-cotización
     */
    public function destroy(ServicioCotizacion $servicioCotizacion)
    {
        // Elimina la relación servicio-cotización y recalcula los montos de la orden afectada.
        $idCotizacion = $servicioCotizacion->id_cotizacion;
        $servicioCotizacion->delete();
        $this->recalcularOrdenCompraPorCotizacion($idCotizacion);

        return response()->json(['message' => 'Eliminado correctamente']);
    }

    private function recalcularOrdenCompraPorCotizacion(int $idCotizacion): void
    {
        $ordenCompra = OrdenCompra::where('id_cotizacion', $idCotizacion)->first();
        $ordenCompra?->recalcularMontoTotal();
    }
}
