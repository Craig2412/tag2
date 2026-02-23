<?php

namespace App\Http\Controllers;

use App\Models\TipoCotizacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoCotizacionController extends Controller
{
    public function index()
    {
        // Lista los tipos de cotizacion y los devuelve en JSON.
        return response()->json(TipoCotizacion::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un tipo de cotizacion con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_cotizacion' => ['required', 'string', 'max:255', 'unique:tipos_cotizaciones,tipo_cotizacion'],
        ]);

        $item = TipoCotizacion::create($data);

        return response()->json($item, 201);
    }

    public function show(TipoCotizacion $tipoCotizacion)
    {
        // Muestra un tipo de cotizacion por id.
        return response()->json($tipoCotizacion);
    }

    public function update(Request $request, TipoCotizacion $tipoCotizacion)
    {
        // Actualiza un tipo de cotizacion y devuelve el resultado.
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

    public function destroy(TipoCotizacion $tipoCotizacion)
    {
        // Elimina el tipo de cotizacion y confirma el resultado.
        $tipoCotizacion->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
