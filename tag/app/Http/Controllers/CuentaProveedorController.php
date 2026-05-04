<?php

namespace App\Http\Controllers;

use App\Models\CuentaProveedor;
use App\Http\Resources\CuentaProveedorResource;
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
     * @bodyParam entidad_financiera string required Nombre del banco. Ejemplo: Banco de Venezuela
     * @bodyParam tipo_cuenta string required Tipo de cuenta (Ahorros / Corriente). Ejemplo: Corriente
     * @bodyParam moneda string required Moneda de la cuenta (VES / USD). Ejemplo: VES
     * @bodyParam id_tipo_contribuyente int required ID del tipo de contribuyente. Ejemplo: 1
     */
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
     * @bodyParam entidad_financiera string Nombre del banco.
     * @bodyParam tipo_cuenta string Tipo de cuenta.
     * @bodyParam moneda string Moneda de la cuenta.
     * @bodyParam id_tipo_contribuyente int ID del tipo de contribuyente.
     */
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
