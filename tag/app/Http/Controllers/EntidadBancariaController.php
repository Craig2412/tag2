<?php

namespace App\Http\Controllers;

use App\Models\EntidadBancaria;
use App\Http\Resources\EntidadBancariaResource;
use Illuminate\Http\Request;

class EntidadBancariaController extends Controller
{
    public function index()
    {
        return EntidadBancariaResource::collection(EntidadBancaria::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entidad' => ['required', 'string', 'max:255'],
            'estatus' => ['nullable', 'exists:estatus,id'],
        ]);
        $entidad = EntidadBancaria::create($data);
        return new EntidadBancariaResource($entidad);
    }

    public function show(EntidadBancaria $entidadBancaria)
    {
        return new EntidadBancariaResource($entidadBancaria);
    }

    public function update(Request $request, EntidadBancaria $entidadBancaria)
    {
        $data = $request->validate([
            'entidad' => ['sometimes', 'required', 'string', 'max:255'],
            'estatus' => ['nullable', 'exists:estatus,id'],
        ]);
        $entidadBancaria->update($data);
        return new EntidadBancariaResource($entidadBancaria);
    }

    public function destroy(EntidadBancaria $entidadBancaria)
    {
        $entidadBancaria->delete();
        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
