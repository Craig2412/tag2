<?php

namespace App\Http\Controllers;

use App\Models\Tasa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TasaController extends Controller
{
    public function index()
    {
        return response()->json(Tasa::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tasa' => ['required', 'string', 'max:255', 'unique:tasas,tasa'],
        ]);

        $item = Tasa::create($data);

        return response()->json($item, 201);
    }

    public function show(Tasa $tasa)
    {
        return response()->json($tasa);
    }

    public function update(Request $request, Tasa $tasa)
    {
        $data = $request->validate([
            'tasa' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tasas', 'tasa')->ignore($tasa->id),
            ],
        ]);

        $tasa->update($data);

        return response()->json($tasa);
    }

    public function destroy(Tasa $tasa)
    {
        $tasa->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
