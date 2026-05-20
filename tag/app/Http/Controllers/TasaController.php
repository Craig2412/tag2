<?php

namespace App\Http\Controllers;

use App\Http\Resources\TasaResource;
use App\Models\Tasa;
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
     */
    public function store(Request $request)
    {
        // Crea una tasa con datos validados y la devuelve.
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:tasas,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'simbolo' => ['required', 'string', 'max:10'],
        ]);

        $item = Tasa::create($data);

        return new TasaResource($item);
    }

    /**
     * Obtener una tasa de gestión específica
     */
    public function show(Tasa $tasa)
    {
        // Muestra una tasa por id.
        return new TasaResource($tasa);
    }

    /**
     * Actualizar una tasa de gestión existente
     */
    public function update(Request $request, Tasa $tasa)
    {
        // Actualiza una tasa y devuelve el resultado.
        $data = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tasas', 'codigo')->ignore($tasa->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'simbolo' => ['required', 'string', 'max:10'],
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
