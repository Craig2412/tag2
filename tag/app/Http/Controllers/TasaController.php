<?php

namespace App\Http\Controllers;

use App\Models\Tasa;
use App\Http\Resources\TasaResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TasaController extends Controller
{
    /**
     * Listar todas las tasas de gestión
     *
     * Devuelve el catálogo de etiquetas para las tasas de gestión administrativa aplicadas.
     */
    public function index()
    {
        // Lista las tasas y las devuelve en JSON.
        return TasaResource::collection(Tasa::orderBy('id')->get());
    }

    /**
     * Crear una nueva tasa de gestión
     * 
     * @bodyParam tasa string required Nombre de la tasa de gestión. Ejemplo: Tasa Administrativa
     */
    public function store(Request $request)
    {
        // Crea una tasa con datos validados y la devuelve.
        $data = $request->validate([
            'tasa' => ['required', 'string', 'max:255', 'unique:tasas,tasa'],
        ]);

        $item = Tasa::create($data);

        return new TasaResource($item);
    }

    /**
     * Obtener una tasa de gestión específica
     *
     * Devuelve los datos de una tasa por su ID.
     */
    public function show(Tasa $tasa)
    {
        // Muestra una tasa por id.
        return new TasaResource($tasa);
    }

    /**
     * Actualizar una tasa de gestión existente
     * 
     * @bodyParam tasa string required Nombre de la tasa.
     */
    public function update(Request $request, Tasa $tasa)
    {
        // Actualiza una tasa y devuelve el resultado.
        $data = $request->validate([
            'tasa' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tasas', 'tasa')->ignore($tasa->id),
            ],
        ]);

        $tasa->update($data);

        return new TasaResource($tasa);
    }

    /**
     * Eliminar una tasa de gestión
     */
    public function destroy(Tasa $tasa)
    {
        // Elimina la tasa y confirma el resultado.
        $tasa->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
