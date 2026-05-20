<?php

namespace App\Http\Controllers;

use App\Http\Resources\EstadoAtencionResource;
use App\Models\EstadoAtencion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoAtencionController extends Controller
{
    /**
     * Listar todos los estados de atención
     *
     * Devuelve el catálogo de estados de atención (abierta, cerrada_ganada, cerrada_perdida).
     */
    public function index()
    {
        return EstadoAtencionResource::collection(EstadoAtencion::orderBy('id')->get());
    }

    /**
     * Crear un nuevo estado de atención
     *
     * @bodyParam slug string required Slug único. Ejemplo: en_proceso
     * @bodyParam nombre string required Nombre descriptivo. Ejemplo: En Proceso
     * @bodyParam color string Código de color hexadecimal. Ejemplo: #F59E0B
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:estados_atenciones,slug'],
            'nombre' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $estado = EstadoAtencion::create($data);

        return new EstadoAtencionResource($estado);
    }

    /**
     * Obtener un estado de atención específico
     */
    public function show(EstadoAtencion $estadoAtencion)
    {
        return new EstadoAtencionResource($estadoAtencion);
    }

    /**
     * Actualizar un estado de atención
     *
     * @bodyParam slug string Slug único. Ejemplo: en_proceso
     * @bodyParam nombre string Nombre descriptivo. Ejemplo: En Proceso
     * @bodyParam color string Código de color hexadecimal. Ejemplo: #F59E0B
     */
    public function update(Request $request, EstadoAtencion $estadoAtencion)
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('estados_atenciones', 'slug')->ignore($estadoAtencion->id)],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $estadoAtencion->update($data);

        return new EstadoAtencionResource($estadoAtencion);
    }

    /**
     * Eliminar un estado de atención
     */
    public function destroy(EstadoAtencion $estadoAtencion)
    {
        $estadoAtencion->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
