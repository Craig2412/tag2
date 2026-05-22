<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrigenResource;
use App\Models\Origen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrigenController extends Controller
{
    /**
     * Listar todos los orígenes de atención
     *
     * Devuelve el catálogo de orígenes (redes sociales, canales, etc) registrados en el sistema.
     */
    public function index()
    {
        // Lista los orígenes y los devuelve en JSON.
        return OrigenResource::collection(Origen::orderBy('id')->get());
    }

    /**
     * Crear un nuevo origen
     *
     * @bodyParam red string required Nombre de la red social o canal de origen. Ejemplo: Instagram
     */
    public function store(Request $request)
    {
        // Crea un origen con datos validados y lo devuelve.
        $data = $request->validate([
            'red' => ['required', 'string', 'max:255', 'unique:origenes,red'],
        ]);

        $origen = Origen::create($data);

        return new OrigenResource($origen);
    }

    /**
     * Obtener un origen específico
     *
     * Devuelve los datos de un origen por su ID.
     */
    public function show(Origen $origen)
    {
        // Muestra un origen por id.
        return new OrigenResource($origen);
    }

    /**
     * Actualizar un origen existente
     *
     * @bodyParam red string required Nombre de la red social o canal.
     */
    public function update(Request $request, Origen $origen)
    {
        // Actualiza un origen y devuelve el resultado.
        $data = $request->validate([
            'red' => [
                'required',
                'string',
                'max:255',
                Rule::unique('origenes', 'red')->ignore($origen->id),
            ],
        ]);

        $origen->update($data);

        return new OrigenResource($origen);
    }

    /**
     * Eliminar un origen
     */
    public function destroy(Origen $origen)
    {
        // Elimina el origen y confirma el resultado.
        $origen->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
