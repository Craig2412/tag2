<?php

namespace App\Http\Controllers;

use App\Http\Resources\TipoContribuyenteResource;
use App\Models\TipoContribuyente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoContribuyenteController extends Controller
{
    /**
     * Listar todos los tipos de contribuyente
     *
     * Devuelve el catálogo de tipos de contribuyentes y sus porcentajes de IVA asociados.
     */
    public function index()
    {
        // Lista los tipos de contribuyentes y los devuelve en JSON.
        return TipoContribuyenteResource::collection(TipoContribuyente::orderBy('id')->get());
    }

    /**
     * Crear un nuevo tipo de contribuyente
     *
     * @bodyParam tipo_contribuyente string required Nombre del tipo de contribuyente. Ejemplo: Especial
     * @bodyParam porcentaje_iva number required Porcentaje de IVA aplicable (0-100). Ejemplo: 16
     */
    public function store(Request $request)
    {
        // Crea un tipo de contribuyente con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_contribuyente' => ['required', 'string', 'max:255', 'unique:tipos_contribuyentes,tipo_contribuyente'],
            'porcentaje_iva' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $item = TipoContribuyente::create($data);

        return new TipoContribuyenteResource($item);
    }

    /**
     * Obtener un tipo de contribuyente específico
     *
     * Devuelve los datos de un tipo de contribuyente por su ID.
     */
    public function show(TipoContribuyente $tipoContribuyente)
    {
        // Muestra un tipo de contribuyente por id.
        return new TipoContribuyenteResource($tipoContribuyente);
    }

    /**
     * Actualizar un tipo de contribuyente
     *
     * @bodyParam tipo_contribuyente string required Nombre del tipo de contribuyente.
     * @bodyParam porcentaje_iva number required Porcentaje de IVA aplicable.
     */
    public function update(Request $request, TipoContribuyente $tipoContribuyente)
    {
        // Actualiza un tipo de contribuyente y devuelve el resultado.
        $data = $request->validate([
            'tipo_contribuyente' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tipos_contribuyentes', 'tipo_contribuyente')->ignore($tipoContribuyente->id),
            ],
            'porcentaje_iva' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $tipoContribuyente->update($data);

        return new TipoContribuyenteResource($tipoContribuyente);
    }

    /**
     * Eliminar un tipo de contribuyente
     */
    public function destroy(TipoContribuyente $tipoContribuyente)
    {
        // Elimina el tipo de contribuyente y confirma el resultado.
        $tipoContribuyente->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
