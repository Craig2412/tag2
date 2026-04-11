<?php

namespace App\Http\Controllers;

use App\Models\Temporalidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemporalidadController extends Controller
{
    /**
     * Listar todas las temporalidades
     *
     * Devuelve el catálogo de intervalos de tiempo (Mensual, Anual, etc) para las metas.
     */
    public function index()
    {
        // Lista las temporalidades y las devuelve en JSON.
        return response()->json(Temporalidad::orderBy('id')->get());
    }

    /**
     * Crear una nueva temporalidad
     * 
     * @bodyParam temporalidad string required Nombre de la temporalidad (ej. Mensual, Semanal). Ejemplo: Mensual
     */
    public function store(Request $request)
    {
        // Crea una temporalidad con datos validados y la devuelve.
        $data = $request->validate([
            'temporalidad' => ['required', 'string', 'max:255', 'unique:temporalidades,temporalidad'],
        ]);

        $item = Temporalidad::create($data);

        return response()->json($item, 201);
    }

    /**
     * Obtener una temporalidad específica
     *
     * Devuelve los datos de una temporalidad por su ID.
     */
    public function show(Temporalidad $temporalidad)
    {
        // Muestra una temporalidad por id.
        return response()->json($temporalidad);
    }

    /**
     * Actualizar una temporalidad existente
     * 
     * @bodyParam temporalidad string required Nombre de la temporalidad.
     */
    public function update(Request $request, Temporalidad $temporalidad)
    {
        // Actualiza una temporalidad y devuelve el resultado.
        $data = $request->validate([
            'temporalidad' => [
                'required',
                'string',
                'max:255',
                Rule::unique('temporalidades', 'temporalidad')->ignore($temporalidad->id),
            ],
        ]);

        $temporalidad->update($data);

        return response()->json($temporalidad);
    }

    /**
     * Eliminar una temporalidad
     */
    public function destroy(Temporalidad $temporalidad)
    {
        // Elimina la temporalidad y confirma el resultado.
        $temporalidad->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
