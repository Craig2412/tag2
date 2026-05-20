<?php

namespace App\Http\Controllers;

use App\Http\Resources\EstadoFinancieroResource;
use App\Models\EstadoFinanciero;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoFinancieroController extends Controller
{
    /**
     * Listar todos los estados financieros
     */
    public function index()
    {
        return EstadoFinancieroResource::collection(EstadoFinanciero::orderBy('id')->get());
    }

    /**
     * Crear un nuevo estado financiero
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:estados_financieros,slug'],
            'label' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $estado = EstadoFinanciero::create($data);

        return new EstadoFinancieroResource($estado);
    }

    /**
     * Obtener un estado financiero específico
     */
    public function show(EstadoFinanciero $estadoFinanciero)
    {
        return new EstadoFinancieroResource($estadoFinanciero);
    }

    /**
     * Actualizar un estado financiero
     */
    public function update(Request $request, EstadoFinanciero $estadoFinanciero)
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('estados_financieros', 'slug')->ignore($estadoFinanciero->id)],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $estadoFinanciero->update($data);

        return new EstadoFinancieroResource($estadoFinanciero);
    }

    /**
     * Eliminar un estado financiero (soft delete)
     */
    public function destroy(EstadoFinanciero $estadoFinanciero)
    {
        $estadoFinanciero->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
