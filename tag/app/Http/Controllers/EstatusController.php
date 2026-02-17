<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstatusController extends Controller
{
    public function index()
    {
        // Lista los estatus y los devuelve en JSON.
        return response()->json(Estatus::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un estatus con datos validados y lo devuelve.
        $data = $request->validate([
            'estatus' => ['required', 'string', 'max:255', 'unique:estatus,estatus'],
        ]);

        $estatus = Estatus::create($data);

        return response()->json($estatus, 201);
    }

    public function show(Estatus $estatus)
    {
        // Muestra un estatus por id.
        return response()->json($estatus);
    }

    public function update(Request $request, Estatus $estatus)
    {
        // Actualiza un estatus y devuelve el resultado.
        $data = $request->validate([
            'estatus' => [
                'required',
                'string',
                'max:255',
                Rule::unique('estatus', 'estatus')->ignore($estatus->id),
            ],
        ]);

        $estatus->update($data);

        return response()->json($estatus);
    }

    public function destroy(Estatus $estatus)
    {
        // Elimina el estatus y confirma el resultado.
        $estatus->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
