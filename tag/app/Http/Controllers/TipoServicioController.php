<?php

namespace App\Http\Controllers;

use App\Models\TipoServicio;
use App\Http\Resources\TipoServicioResource;
use Illuminate\Http\Request;

class TipoServicioController extends Controller
{
    /**
     * Listar todos los tipos de servicio
     *
     * Devuelve el catálogo de tipos de servicio activos.
     */
    public function index()
    {
        // Eloquent maneja SoftDeletes automáticamente
        $tipos = TipoServicio::orderBy('id')->get();

        return TipoServicioResource::collection($tipos);
    }

    /**
     * Crear un nuevo tipo de servicio
     * 
     * @bodyParam tipo_servicio string required Nombre descriptivo del tipo de servicio. Ejemplo: Emisión de Boletos
     * @bodyParam id_proveedor int required ID del proveedor principal para este tipo de servicio. Ejemplo: 1
     * @bodyParam iva_defecto number optional Porcentaje de IVA por defecto para este tipo de servicio.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_servicio' => ['required', 'string', 'max:255'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'iva_defecto' => ['nullable', 'numeric'],
        ]);

        $tipoServicio = TipoServicio::create($data);

        return new TipoServicioResource($tipoServicio);
    }

    /**
     * Obtener un tipo de servicio específico
     */
    public function show(TipoServicio $tipoServicio)
    {
        return new TipoServicioResource($tipoServicio);
    }

    /**
     * Actualizar un tipo de servicio existente
     * 
     * @bodyParam tipo_servicio string Nombre del tipo de servicio.
     * @bodyParam id_proveedor int ID del proveedor asociado.
     * @bodyParam iva_defecto number Porcentaje de IVA por defecto.
     */
    public function update(Request $request, TipoServicio $tipoServicio)
    {
        $data = $request->validate([
            'tipo_servicio' => ['sometimes', 'required', 'string', 'max:255'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'iva_defecto' => ['nullable', 'numeric'],
        ]);

        $tipoServicio->update($data);

        return new TipoServicioResource($tipoServicio);
    }

    /**
     * Eliminar un tipo de servicio
     * Usa SoftDeletes nativo de Eloquent.
     */
    public function destroy(TipoServicio $tipoServicio)
    {
        $tipoServicio->delete();
        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
