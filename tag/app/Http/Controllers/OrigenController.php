<?php

namespace App\Http\Controllers;

use App\Models\Origen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrigenController extends Controller
{
    public function index()
    {
        // Lista los origenes y los devuelve en JSON.
        return response()->json(Origen::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un origen con datos validados y lo devuelve.
        $data = $request->validate([
            'red' => ['required', 'string', 'max:255', 'unique:origenes,red'],
        ]);

        $origen = Origen::create($data);

        return response()->json($origen, 201);
    }

    public function show(Origen $origen)
    {
        // Muestra un origen por id.
        return response()->json($origen);
    }

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

        return response()->json($origen);
    }

    public function destroy(Origen $origen)
    {
        // Elimina el origen y confirma el resultado.
        $origen->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
