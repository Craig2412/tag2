<?php

namespace App\Http\Controllers;

use App\Models\TipoContribuyente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoContribuyenteController extends Controller
{
    public function index()
    {
        // Lista los tipos de contribuyentes y los devuelve en JSON.
        return response()->json(TipoContribuyente::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un tipo de contribuyente con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_contribuyente' => ['required', 'string', 'max:255', 'unique:tipos_contribuyentes,tipo_contribuyente'],
            'porcentaje_iva' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $item = TipoContribuyente::create($data);

        return response()->json($item, 201);
    }

    public function show(TipoContribuyente $tipoContribuyente)
    {
        // Muestra un tipo de contribuyente por id.
        return response()->json($tipoContribuyente);
    }

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

        return response()->json($tipoContribuyente);
    }

    public function destroy(TipoContribuyente $tipoContribuyente)
    {
        // Elimina el tipo de contribuyente y confirma el resultado.
        $tipoContribuyente->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
