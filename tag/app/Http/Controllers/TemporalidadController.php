<?php

namespace App\Http\Controllers;

use App\Models\Temporalidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemporalidadController extends Controller
{
    public function index()
    {
        return response()->json(Temporalidad::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'temporalidad' => ['required', 'string', 'max:255', 'unique:temporalidades,temporalidad'],
        ]);

        $item = Temporalidad::create($data);

        return response()->json($item, 201);
    }

    public function show(Temporalidad $temporalidad)
    {
        return response()->json($temporalidad);
    }

    public function update(Request $request, Temporalidad $temporalidad)
    {
        $data = $request->validate([
            'temporalidad' => [
                'required',
                'string',
                'max:255',
                Rule::unique('temporalidades', 'temporalidad')->ignore($temporalidad->id),
            ],
        ]);

        $temporalidad->update($data);

        return response()->json($temporalidad);
    }

    public function destroy(Temporalidad $temporalidad)
    {
        $temporalidad->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
