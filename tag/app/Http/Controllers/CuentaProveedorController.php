<?php

namespace App\Http\Controllers;

use App\Http\Resources\CuentaProveedorResource;
use App\Models\CuentaProveedor;
use Illuminate\Http\Request;

class CuentaProveedorController extends Controller
{
    /**
     * Listar todas las cuentas bancarias de proveedores
     *
     * Devuelve todas las cuentas bancarias registradas para los proveedores del sistema.
     */
    public function index()
    {
        // Lista las cuentas de proveedores y las devuelve en JSON.
        return CuentaProveedorResource::collection(CuentaProveedor::orderBy('id')->get());
    }

    /**
     * Registrar cuenta bancaria de proveedor
     *
     * Agrega una nueva cuenta bancaria asociada a un proveedor existente.
     *
     * @bodyParam id_proveedor int required ID del proveedor. Ejemplo: 1
     * @bodyParam numero_cuenta string required Número de cuenta bancaria. Ejemplo: 0102-0000-000000000000
     * @bodyParam nombre_banco string required Nombre del banco del proveedor. Ejemplo: Citibank
     * @bodyParam tipo_cuenta string required Tipo de cuenta (Ahorros / Corriente). Ejemplo: Corriente
     * @bodyParam moneda string required Moneda de la cuenta (VES / USD). Ejemplo: VES
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'numero_cuenta' => ['required', 'string', 'max:255'],
            'nombre_banco' => ['required', 'string', 'max:255'],
            'tipo_cuenta' => ['required', 'string', 'max:255'],
            'moneda' => ['required', 'string', 'max:50'],
        ]);

        $item = CuentaProveedor::create($data);

        return new CuentaProveedorResource($item);
    }

    /**
     * Obtener una cuenta bancaria de proveedor específica
     *
     * Devuelve los datos de una cuenta bancaria por su ID.
     */
    public function show(CuentaProveedor $cuentaProveedor)
    {
        // Muestra una cuenta de proveedor por id.
        return new CuentaProveedorResource($cuentaProveedor);
    }

    /**
     * Actualizar cuenta bancaria de proveedor
     *
     * Modifica los datos de una cuenta bancaria registrada para un proveedor.
     *
     * @bodyParam id_proveedor int ID del proveedor.
     * @bodyParam numero_cuenta string Número de cuenta bancaria.
     * @bodyParam nombre_banco string Nombre del banco del proveedor.
     * @bodyParam tipo_cuenta string Tipo de cuenta.
     * @bodyParam moneda string Moneda de la cuenta.
     */
    public function update(Request $request, CuentaProveedor $cuentaProveedor)
    {
        $data = $request->validate([
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'numero_cuenta' => ['sometimes', 'required', 'string', 'max:255'],
            'nombre_banco' => ['sometimes', 'required', 'string', 'max:255'],
            'tipo_cuenta' => ['sometimes', 'required', 'string', 'max:255'],
            'moneda' => ['sometimes', 'required', 'string', 'max:50'],
        ]);

        $cuentaProveedor->update($data);

        return new CuentaProveedorResource($cuentaProveedor);
    }

    /**
     * Eliminar cuenta bancaria de proveedor
     *
     * Elimina permanentemente la cuenta bancaria del proveedor.
     */
    public function destroy(CuentaProveedor $cuentaProveedor)
    {
        // Elimina la cuenta de proveedor y confirma el resultado.
        $cuentaProveedor->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
