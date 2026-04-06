<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    /**
     * Listar todos los servicios
     *
     * Devuelve el listado de servicios activos registrados en el sistema.
     */
    public function index()
    {
        $servicios = Servicio::with(['tipoServicio', 'proveedor', 'tasaCambio'])
            ->orderBy('id')
            ->get();

        return response()->json($servicios);
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
            'id_tipo_servicio' => ['required', 'exists:tipos_servicios,id'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
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

        return response()->json($servicio->load(['tipoServicio', 'proveedor', 'tasaCambio']), 201);
    }

    /**
     * Obtener un servicio específico
     */
    public function show(Servicio $servicio)
    {
        return response()->json($servicio->load(['tipoServicio', 'proveedor', 'tasaCambio']));
    }

    /**
     * Actualizar un servicio existente
     */
    public function update(Request $request, Servicio $servicio)
    {
        $data = $request->validate([
            'id_tipo_servicio' => ['sometimes', 'required', 'exists:tipos_servicios,id'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'costo' => ['sometimes', 'required', 'numeric', 'min:0'],
            'monto_gravable' => ['sometimes', 'required', 'numeric', 'min:0'],
            'monto_no_sujeto' => ['sometimes', 'required', 'numeric', 'min:0'],
            'iva_establecido' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
        ]);

        // La sumatoria real es manejada en el ServicioObserver de manera autónoma.
        $servicio->update($data);

        return response()->json($servicio->fresh()->load(['tipoServicio', 'proveedor', 'tasaCambio']));
    }

    /**
     * Eliminar un servicio
     * Usa SoftDeletes nativo de Eloquent.
     */
    public function destroy(Servicio $servicio)
    {
        $servicio->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
