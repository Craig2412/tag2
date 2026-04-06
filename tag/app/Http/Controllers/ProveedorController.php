<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    /**
     * Listar todos los proveedores
     *
     * Devuelve todos los proveedores activos del sistema, incluyendo su clasificación y atribución fiscal.
     */
    public function index()
    {
        // El Scope SoftDeletes automáticamente filtra los eliminados
        $proveedores = Proveedor::with(['tipoProveedor', 'tipoContribuyente'])
            ->orderBy('id')
            ->get();

        return response()->json($proveedores);
    }

    /**
     * Crear un nuevo proveedor
     *
     * Registra un nuevo proveedor de servicios en el sistema.
     *
     * @bodyParam nombre_empresa string required Razón social de la empresa. Ejemplo: Suministros Globales S.A.
     * @bodyParam razon_comercial string required Nombre comercial. Ejemplo: Global Sum
     * @bodyParam rif string required RIF de la empresa. Ejemplo: J-87654321-0
     * @bodyParam correo_empresa string required Correo electrónico de contacto. Ejemplo: ventas@globalsum.com
     * @bodyParam telefono_empresa string Teléfono de la empresa. Ejemplo: +58 212 999 8888
     * @bodyParam nombre_persona_contacto string required Nombre de la persona de contacto. Ejemplo: María Rodríguez
     * @bodyParam tipo_proveedor int required ID del tipo de proveedor. Ejemplo: 1
     * @bodyParam id_tipo_contribuyente int required ID de la denominación fiscal/contribuyente. Ejemplo: 1
     * @bodyParam estatus int required ID del estatus. Ejemplo: 1
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:255'],
            'razon_comercial' => ['required', 'string', 'max:255'],
            'rif' => ['required', 'string', 'max:50', 'unique:proveedores,rif'],
            'correo_empresa' => ['required', 'email', 'max:255', 'unique:proveedores,correo_empresa'],
            'telefono_empresa' => ['nullable', 'string', 'max:50'],
            'nombre_persona_contacto' => ['required', 'string', 'max:255'],
            'tipo_proveedor' => ['required', 'exists:tipos_proveedores,id'],
            'id_tipo_contribuyente' => ['required', 'exists:tipos_contribuyentes,id'],
            'estatus' => ['required', 'exists:estatus,id'],
        ]);

        $proveedor = Proveedor::create($data);

        return response()->json($proveedor->load(['tipoProveedor', 'tipoContribuyente']), 201);
    }

    /**
     * Obtener un proveedor específico
     *
     * Devuelve los datos de un proveedor por su ID.
     */
    public function show(Proveedor $proveedor)
    {
        return response()->json($proveedor->load(['tipoProveedor', 'tipoContribuyente']));
    }

    /**
     * Actualizar un proveedor existente
     *
     * Modifica los datos de un proveedor activo.
     *
     * @bodyParam nombre_empresa string Razón social de la empresa.
     * @bodyParam razon_comercial string Nombre comercial.
     * @bodyParam rif string RIF de la empresa.
     * @bodyParam correo_empresa string Correo electrónico de contacto.
     * @bodyParam telefono_empresa string Teléfono de la empresa.
     * @bodyParam nombre_persona_contacto string Nombre de la persona de contacto.
     * @bodyParam tipo_proveedor int ID del tipo de proveedor.
     * @bodyParam id_tipo_contribuyente int ID de la denominación fiscal/contribuyente.
     * @bodyParam estatus int ID del estatus.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $data = $request->validate([
            'nombre_empresa' => ['sometimes', 'required', 'string', 'max:255'],
            'razon_comercial' => ['sometimes', 'required', 'string', 'max:255'],
            'rif' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('proveedores', 'rif')->ignore($proveedor->id),
            ],
            'correo_empresa' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores', 'correo_empresa')->ignore($proveedor->id),
            ],
            'telefono_empresa' => ['sometimes', 'nullable', 'string', 'max:50'],
            'nombre_persona_contacto' => ['sometimes', 'required', 'string', 'max:255'],
            'tipo_proveedor' => ['sometimes', 'required', 'exists:tipos_proveedores,id'],
            'id_tipo_contribuyente' => ['sometimes', 'required', 'exists:tipos_contribuyentes,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
        ]);

        $proveedor->update($data);

        return response()->json($proveedor->load(['tipoProveedor', 'tipoContribuyente']));
    }

    /**
     * Eliminar un proveedor
     *
     * Realiza la eliminación lógica del proveedor usando el SoftDeletes nativo de Eloquent.
     */
    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
