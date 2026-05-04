<?php

namespace App\Http\Controllers;

use App\Models\TipoProveedor;
use App\Http\Resources\TipoProveedorResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoProveedorController extends Controller
{
    /**
     * Listar todos los tipos de proveedor
     *
     * Devuelve el catálogo completo de tipos de proveedor registrados.
     */
    public function index()
    {
        // Lista los tipos de proveedor y los devuelve en JSON.
        return TipoProveedorResource::collection(TipoProveedor::orderBy('id')->get());
    }

    /**
     * Crear un nuevo tipo de proveedor
     *
     * @bodyParam tipo_proveedor string required Nombre del tipo de proveedor. Ejemplo: Mayorista
     */
    public function store(Request $request)
    {
        // Crea un tipo de proveedor con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_proveedor' => ['required', 'string', 'max:255', 'unique:tipos_proveedores,tipo_proveedor'],
        ]);

        $tipoProveedor = TipoProveedor::create($data);

        return new TipoProveedorResource($tipoProveedor);
    }

    /**
     * Obtener un tipo de proveedor específico
     *
     * Devuelve los datos de un tipo de proveedor por su ID.
     */
    public function show(TipoProveedor $tipoProveedor)
    {
        // Muestra un tipo de proveedor por id.
        return new TipoProveedorResource($tipoProveedor);
    }

    /**
     * Actualizar un tipo de proveedor
     *
     * @bodyParam tipo_proveedor string required Nombre del tipo de proveedor.
     */
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

        return new TipoProveedorResource($tipoProveedor);
    }

    /**
     * Eliminar un tipo de proveedor
     *
     * Elimina permanentemente el tipo de proveedor del sistema.
     */
    public function destroy(TipoProveedor $tipoProveedor)
    {
        // Elimina el tipo de proveedor y confirma el resultado.
        $tipoProveedor->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
