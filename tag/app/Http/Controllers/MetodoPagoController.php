<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MetodoPagoController extends Controller
{
    public function index()
    {
        // Lista los metodos de pago y los devuelve en JSON.
        return response()->json(MetodoPago::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un metodo de pago con datos validados y lo devuelve.
        $data = $request->validate([
            'metodo_pago' => ['required', 'string', 'max:255', 'unique:metodos_pago,metodo_pago'],
        ]);

        $item = MetodoPago::create($data);

        return response()->json($item, 201);
    }

    public function show(MetodoPago $metodoPago)
    {
        // Muestra un metodo de pago por id.
        return response()->json($metodoPago);
    }

    public function update(Request $request, MetodoPago $metodoPago)
    {
        // Actualiza un metodo de pago y devuelve el resultado.
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
        // Elimina el metodo de pago y confirma el resultado.
        $metodoPago->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
