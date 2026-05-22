<?php

namespace App\Http\Controllers;

use App\Http\Resources\EstadoOrdenCompraResource;
use App\Models\EstadoOrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoOrdenCompraController extends Controller
{
    /**
     * Listar todos los estados de orden de compra
     */
    public function index()
    {
        return EstadoOrdenCompraResource::collection(EstadoOrdenCompra::orderBy('id')->get());
    }

    /**
     * Crear un nuevo estado de orden de compra
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:estados_ordenes_compra,slug'],
            'nombre' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $estado = EstadoOrdenCompra::create($data);

        return new EstadoOrdenCompraResource($estado);
    }

    /**
     * Obtener un estado de orden de compra específico
     */
    public function show(EstadoOrdenCompra $estadoOrdenCompra)
    {
        return new EstadoOrdenCompraResource($estadoOrdenCompra);
    }

    /**
     * Actualizar un estado de orden de compra
     */
    public function update(Request $request, EstadoOrdenCompra $estadoOrdenCompra)
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('estados_ordenes_compra', 'slug')->ignore($estadoOrdenCompra->id)],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $estadoOrdenCompra->update($data);

        return new EstadoOrdenCompraResource($estadoOrdenCompra);
    }

    /**
     * Eliminar un estado de orden de compra
     */
    public function destroy(EstadoOrdenCompra $estadoOrdenCompra)
    {
        $estadoOrdenCompra->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
