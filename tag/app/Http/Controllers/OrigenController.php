<?php

namespace App\Http\Controllers;

use App\Models\Origen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrigenController extends Controller
{
    public function index()
    {
        return response()->json(Origen::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'red' => ['required', 'string', 'max:255', 'unique:origenes,red'],
        ]);

        $origen = Origen::create($data);

        return response()->json($origen, 201);
    }

    public function show(Origen $origen)
    {
        return response()->json($origen);
    }

    public function update(Request $request, Origen $origen)
    {
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
        $origen->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
