<?php

namespace App\Http\Controllers;

use App\Http\Resources\TemporalidadResource;
use App\Models\Temporalidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemporalidadController extends Controller
{
    /**
     * Listar todas las temporalidades
     */
    public function index()
    {
        return TemporalidadResource::collection(Temporalidad::orderBy('id')->get());
    }

    /**
     * Crear una nueva temporalidad
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'temporalidad' => ['required', 'string', 'max:255', 'unique:temporalidades,temporalidad'],
            'carbon_method' => ['required', 'string', 'max:255'],
        ]);

        $data['slug'] = $data['carbon_method'];

        $item = Temporalidad::create($data);

        return new TemporalidadResource($item);
    }

    /**
     * Obtener una temporalidad específica
     */
    public function show(Temporalidad $temporalidad)
    {
        return new TemporalidadResource($temporalidad);
    }

    /**
     * Actualizar una temporalidad existente
     */
    public function update(Request $request, Temporalidad $temporalidad)
    {
        $data = $request->validate([
            'temporalidad' => [
                'required',
                'string',
                'max:255',
                Rule::unique('temporalidades', 'temporalidad')->ignore($temporalidad->id),
            ],
            'carbon_method' => ['required', 'string', 'max:255'],
        ]);

        $data['slug'] = $data['carbon_method'];

        $temporalidad->update($data);

        return new TemporalidadResource($temporalidad);
    }

    /**
     * Eliminar una temporalidad
     */
    public function destroy(Temporalidad $temporalidad)
    {
        $temporalidad->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
