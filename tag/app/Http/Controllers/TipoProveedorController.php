<?php

namespace App\Http\Controllers;

use App\Models\TipoProveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoProveedorController extends Controller
{
    public function index()
    {
        return response()->json(TipoProveedor::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_proveedor' => ['required', 'string', 'max:255', 'unique:tipos_proveedores,tipo_proveedor'],
        ]);

        $tipoProveedor = TipoProveedor::create($data);

        return response()->json($tipoProveedor, 201);
    }

    public function show(TipoProveedor $tipoProveedor)
    {
        return response()->json($tipoProveedor);
    }

    public function update(Request $request, TipoProveedor $tipoProveedor)
    {
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
        $tipoProveedor->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
