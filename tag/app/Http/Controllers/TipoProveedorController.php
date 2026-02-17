<?php

namespace App\Http\Controllers;

use App\Models\TipoProveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoProveedorController extends Controller
{
    public function index()
    {
        // Lista los tipos de proveedor y los devuelve en JSON.
        return response()->json(TipoProveedor::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un tipo de proveedor con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_proveedor' => ['required', 'string', 'max:255', 'unique:tipos_proveedores,tipo_proveedor'],
        ]);

        $tipoProveedor = TipoProveedor::create($data);

        return response()->json($tipoProveedor, 201);
    }

    public function show(TipoProveedor $tipoProveedor)
    {
        // Muestra un tipo de proveedor por id.
        return response()->json($tipoProveedor);
    }

    public function update(Request $request, TipoProveedor $tipoProveedor)
    {
        // Actualiza un tipo de proveedor y devuelve el resultado.
        $data = $request->validate([
            'tipo_proveedor' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tipos_proveedores', 'tipo_proveedor')->ignore($tipoProveedor->id),
            ],
        ]);

        $tipoProveedor->update($data);

        return response()->json($tipoProveedor);
    }

    public function destroy(TipoProveedor $tipoProveedor)
    {
        // Elimina el tipo de proveedor y confirma el resultado.
        $tipoProveedor->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
