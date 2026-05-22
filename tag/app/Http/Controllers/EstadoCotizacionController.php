<?php

namespace App\Http\Controllers;

use App\Http\Resources\EstadoCotizacionResource;
use App\Models\EstadoCotizacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoCotizacionController extends Controller
{
    /**
     * Listar todos los estados de cotización
     */
    public function index()
    {
        return EstadoCotizacionResource::collection(EstadoCotizacion::orderBy('id')->get());
    }

    /**
     * Crear un nuevo estado de cotización
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:estados_cotizaciones,slug'],
            'nombre' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $estado = EstadoCotizacion::create($data);

        return new EstadoCotizacionResource($estado);
    }

    /**
     * Obtener un estado de cotización específico
     */
    public function show(EstadoCotizacion $estadoCotizacion)
    {
        return new EstadoCotizacionResource($estadoCotizacion);
    }

    /**
     * Actualizar un estado de cotización
     */
    public function update(Request $request, EstadoCotizacion $estadoCotizacion)
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('estados_cotizaciones', 'slug')->ignore($estadoCotizacion->id)],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $estadoCotizacion->update($data);

        return new EstadoCotizacionResource($estadoCotizacion);
    }

    /**
     * Eliminar un estado de cotización
     */
    public function destroy(EstadoCotizacion $estadoCotizacion)
    {
        $estadoCotizacion->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
