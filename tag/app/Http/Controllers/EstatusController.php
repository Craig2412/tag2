<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstatusController extends Controller
{
    public function index()
    {
        return response()->json(Estatus::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'estatus' => ['required', 'string', 'max:255', 'unique:estatus,estatus'],
        ]);

        $estatus = Estatus::create($data);

        return response()->json($estatus, 201);
    }

    public function show(Estatus $estatus)
    {
        return response()->json($estatus);
    }

    public function update(Request $request, Estatus $estatus)
    {
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
        $estatus->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
