<?php

namespace App\Http\Controllers;

use App\Models\Meta;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function index()
    {
        return response()->json(Meta::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cant_atenciones_aprobadas' => ['required', 'integer', 'min:0'],
            'cant_cotizaciones_cerradas' => ['required', 'integer', 'min:0'],
            'cant_cotizaciones_pagadas' => ['required', 'integer', 'min:0'],
            'id_temporalidad' => ['required', 'exists:temporalidades,id'],
        ]);

        $item = Meta::create($data);

        return response()->json($item, 201);
    }

    public function show(Meta $meta)
    {
        return response()->json($meta);
    }

    public function update(Request $request, Meta $meta)
    {
        $data = $request->validate([
            'cant_atenciones_aprobadas' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_cotizaciones_cerradas' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_cotizaciones_pagadas' => ['sometimes', 'required', 'integer', 'min:0'],
            'id_temporalidad' => ['sometimes', 'required', 'exists:temporalidades,id'],
        ]);

        $meta->update($data);

        return response()->json($meta);
    }

    public function destroy(Meta $meta)
    {
        $meta->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
