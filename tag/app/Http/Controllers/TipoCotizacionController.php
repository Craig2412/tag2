<?php

namespace App\Http\Controllers;

use App\Models\TipoCotizacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoCotizacionController extends Controller
{
    /**
     * Listar todos los tipos de cotización
     *
     * Devuelve el catálogo de tipos de cotización (Nacional, Internacional, etc).
     */
    public function index()
    {
        // Lista los tipos de cotización y los devuelve en JSON.
        return response()->json(TipoCotizacion::orderBy('id')->get());
    }

    /**
     * Crear un nuevo tipo de cotización
     * 
     * @bodyParam tipo_cotizacion string required Nombre del tipo de cotización. Ejemplo: Nacional
     */
    public function store(Request $request)
    {
        // Crea un tipo de cotización con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_cotizacion' => ['required', 'string', 'max:255', 'unique:tipos_cotizaciones,tipo_cotizacion'],
        ]);

        $item = TipoCotizacion::create($data);

        return response()->json($item, 201);
    }

    /**
     * Obtener un tipo de cotización específico
     *
     * Devuelve los datos de un tipo de cotización por su ID.
     */
    public function show(TipoCotizacion $tipoCotizacion)
    {
        // Muestra un tipo de cotización por id.
        return response()->json($tipoCotizacion);
    }

    /**
     * Actualizar un tipo de cotización existente
     * 
     * @bodyParam tipo_cotizacion string required Nombre del tipo de cotización.
     */
    public function update(Request $request, TipoCotizacion $tipoCotizacion)
    {
        // Actualiza un tipo de cotización y devuelve el resultado.
        $data = $request->validate([
            'tipo_cotizacion' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tipos_cotizaciones', 'tipo_cotizacion')->ignore($tipoCotizacion->id),
            ],
        ]);

        $tipoCotizacion->update($data);

        return response()->json($tipoCotizacion);
    }

    /**
     * Eliminar un tipo de cotización
     */
    public function destroy(TipoCotizacion $tipoCotizacion)
    {
        // Elimina el tipo de cotización y confirma el resultado.
        $tipoCotizacion->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
