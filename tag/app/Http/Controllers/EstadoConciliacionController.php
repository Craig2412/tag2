<?php

namespace App\Http\Controllers;

use App\Http\Resources\EstadoConciliacionResource;
use App\Models\EstadoConciliacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoConciliacionController extends Controller
{
    /**
     * Listar todos los estados de conciliación
     */
    public function index()
    {
        return EstadoConciliacionResource::collection(EstadoConciliacion::orderBy('id')->get());
    }

    /**
     * Crear un nuevo estado de conciliación
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'unique:estados_conciliacion,slug'],
            'nombre' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $estado = EstadoConciliacion::create($data);

        return new EstadoConciliacionResource($estado);
    }

    /**
     * Obtener un estado de conciliación específico
     */
    public function show(EstadoConciliacion $estadoConciliacion)
    {
        return new EstadoConciliacionResource($estadoConciliacion);
    }

    /**
     * Actualizar un estado de conciliación
     */
    public function update(Request $request, EstadoConciliacion $estadoConciliacion)
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('estados_conciliacion', 'slug')->ignore($estadoConciliacion->id)],
            'nombre' => ['sometimes', 'required', 'string', 'max:50'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $estadoConciliacion->update($data);

        return new EstadoConciliacionResource($estadoConciliacion);
    }

    /**
     * Eliminar un estado de conciliación
     */
    public function destroy(EstadoConciliacion $estadoConciliacion)
    {
        $estadoConciliacion->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
