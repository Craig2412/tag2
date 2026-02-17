<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MetodoPagoController extends Controller
{
    public function index()
    {
        return response()->json(MetodoPago::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'metodo_pago' => ['required', 'string', 'max:255', 'unique:metodos_pago,metodo_pago'],
        ]);

        $item = MetodoPago::create($data);

        return response()->json($item, 201);
    }

    public function show(MetodoPago $metodoPago)
    {
        return response()->json($metodoPago);
    }

    public function update(Request $request, MetodoPago $metodoPago)
    {
        $data = $request->validate([
            'metodo_pago' => [
                'required',
                'string',
                'max:255',
                Rule::unique('metodos_pago', 'metodo_pago')->ignore($metodoPago->id),
            ],
        ]);

        $metodoPago->update($data);

        return response()->json($metodoPago);
    }

    public function destroy(MetodoPago $metodoPago)
    {
        $metodoPago->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
