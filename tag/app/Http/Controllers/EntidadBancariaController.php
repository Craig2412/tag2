<?php

namespace App\Http\Controllers;

use App\Models\EntidadBancaria;
use Illuminate\Http\Request;

class EntidadBancariaController extends Controller
{
    public function index()
    {
        return response()->json(EntidadBancaria::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entidad' => ['required', 'string', 'max:255'],
            'estatus' => ['nullable', 'exists:estatus,id'],
        ]);
        $entidad = EntidadBancaria::create($data);
        return response()->json($entidad, 201);
    }

    public function show(EntidadBancaria $entidadBancaria)
    {
        return response()->json($entidadBancaria);
    }

    public function update(Request $request, EntidadBancaria $entidadBancaria)
    {
        $data = $request->validate([
            'entidad' => ['sometimes', 'required', 'string', 'max:255'],
            'estatus' => ['nullable', 'exists:estatus,id'],
        ]);
        $entidadBancaria->update($data);
        return response()->json($entidadBancaria);
    }

    public function destroy(EntidadBancaria $entidadBancaria)
    {
        $entidadBancaria->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
