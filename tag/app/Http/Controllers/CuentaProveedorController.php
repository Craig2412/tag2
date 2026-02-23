<?php

namespace App\Http\Controllers;

use App\Models\CuentaProveedor;
use Illuminate\Http\Request;

class CuentaProveedorController extends Controller
{
    public function index()
    {
        // Lista las cuentas de proveedores y las devuelve en JSON.
        return response()->json(CuentaProveedor::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea una cuenta de proveedor con datos validados y la devuelve.
        $data = $request->validate([
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'numero_cuenta' => ['required', 'string', 'max:255'],
            'entidad_financiera' => ['required', 'string', 'max:255'],
            'tipo_cuenta' => ['required', 'string', 'max:255'],
            'moneda' => ['required', 'string', 'max:50'],
            'id_tipo_contribuyente' => ['required', 'exists:tipos_contribuyentes,id'],
        ]);

        $item = CuentaProveedor::create($data);

        return response()->json($item, 201);
    }

    public function show(CuentaProveedor $cuentaProveedor)
    {
        // Muestra una cuenta de proveedor por id.
        return response()->json($cuentaProveedor);
    }

    public function update(Request $request, CuentaProveedor $cuentaProveedor)
    {
        // Actualiza una cuenta de proveedor y devuelve el resultado.
        $data = $request->validate([
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'numero_cuenta' => ['sometimes', 'required', 'string', 'max:255'],
            'entidad_financiera' => ['sometimes', 'required', 'string', 'max:255'],
            'tipo_cuenta' => ['sometimes', 'required', 'string', 'max:255'],
            'moneda' => ['sometimes', 'required', 'string', 'max:50'],
            'id_tipo_contribuyente' => ['sometimes', 'required', 'exists:tipos_contribuyentes,id'],
        ]);

        $cuentaProveedor->update($data);

        return response()->json($cuentaProveedor);
    }

    public function destroy(CuentaProveedor $cuentaProveedor)
    {
        // Elimina la cuenta de proveedor y confirma el resultado.
        $cuentaProveedor->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
