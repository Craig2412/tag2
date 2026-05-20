<?php

namespace App\Http\Controllers;

use App\Http\Resources\TipoServicioResource;
use App\Models\TipoServicio;
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
        $tipos = TipoServicio::with('proveedores')->orderBy('id')->get();

        return TipoServicioResource::collection($tipos);
    }

    /**
     * Crear un nuevo tipo de servicio
     *
     * @bodyParam tipo_servicio string required Nombre descriptivo del tipo de servicio. Ejemplo: Emisión de Boletos
     * @bodyParam iva_defecto number optional Porcentaje de IVA por defecto para este tipo de servicio.
     * @bodyParam proveedores int[] Lista de IDs de proveedores que ofrecen este servicio. Example: [1, 2]
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_servicio' => ['required', 'string', 'max:255'],
            'iva_defecto' => ['nullable', 'numeric'],
            'proveedores' => ['nullable', 'array'],
            'proveedores.*' => ['exists:proveedores,id'],
        ]);

        $tipoServicio = TipoServicio::create($data);

        if (isset($data['proveedores'])) {
            $tipoServicio->proveedores()->sync($data['proveedores']);
        }

        return new TipoServicioResource($tipoServicio->load('proveedores'));
    }

    /**
     * Obtener un tipo de servicio específico
     */
    public function show(TipoServicio $tipoServicio)
    {
        return new TipoServicioResource($tipoServicio->load('proveedores'));
    }

    /**
     * Actualizar un tipo de servicio existente
     *
     * @bodyParam tipo_servicio string Nombre del tipo de servicio.
     * @bodyParam iva_defecto number Porcentaje de IVA por defecto.
     * @bodyParam proveedores int[] Lista de IDs de proveedores que ofrecen este servicio. Example: [1, 2]
     */
    public function update(Request $request, TipoServicio $tipoServicio)
    {
        $data = $request->validate([
            'tipo_servicio' => ['sometimes', 'required', 'string', 'max:255'],
            'iva_defecto' => ['nullable', 'numeric'],
            'proveedores' => ['nullable', 'array'],
            'proveedores.*' => ['exists:proveedores,id'],
        ]);

        $tipoServicio->update($data);

        if (isset($data['proveedores'])) {
            $tipoServicio->proveedores()->sync($data['proveedores']);
        }

        return new TipoServicioResource($tipoServicio->load('proveedores'));
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
