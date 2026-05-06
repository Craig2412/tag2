<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Http\Resources\ProveedorResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    /**
     * Listar todos los proveedores
     *
     * Devuelve todos los proveedores activos del sistema, incluyendo su clasificación y atribución fiscal.
     */
    public function index(Request $request)
    {
        $query = Proveedor::query();

        if ($request->has('include')) {
            $allowed = ['tipoProveedor', 'tipoContribuyente', 'tiposServicio', 'cuentas'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (!empty($includes)) {
                $query->with(collect($includes)->mapWithKeys(function ($include) {
                    if ($include === 'tipoProveedor' || $include === 'tipoContribuyente') {
                        return [$include => fn($q) => $q->withTrashed()];
                    }
                    return [$include => fn($q) => $q];
                })->toArray());
            }
        }

        return ProveedorResource::collection($query->orderBy('id')->get());
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
     * @bodyParam tipos_servicio int[] Lista de IDs de Tipos de Servicio autorizados. Example: [1, 2]
     * @bodyParam cuentas object[] Lista de cuentas bancarias asociadas al proveedor.
     * @bodyParam cuentas[].numero_cuenta string required El número de la cuenta. Example: 01021111222233334444
     * @bodyParam cuentas[].nombre_banco int required ID de la entidad bancaria. Example: 1
     * @bodyParam cuentas[].tipo_cuenta string required Tipo de cuenta bancaria. Example: Corriente
     * @bodyParam cuentas[].moneda string required Moneda de la cuenta. Example: VES
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
            'tipo_proveedor' => ['required', Rule::exists('tipos_proveedores', 'id')->whereNull('deleted_at')],
            'id_tipo_contribuyente' => ['required', Rule::exists('tipos_contribuyentes', 'id')->whereNull('deleted_at')],
            'tipos_servicio' => ['nullable', 'array'],
            'tipos_servicio.*' => [Rule::exists('tipo_servicio', 'id')->whereNull('deleted_at')],
            'cuentas' => ['nullable', 'array'],
            'cuentas.*.numero_cuenta' => ['required', 'string', 'max:255'],
            'cuentas.*.nombre_banco' => ['required', 'string', 'max:255'],
            'cuentas.*.tipo_cuenta' => ['required', 'string', 'max:255'],
            'cuentas.*.moneda' => ['required', 'string', 'max:255'],
        ]);

        $proveedor = Proveedor::create($data);

        if (isset($data['tipos_servicio'])) {
            $proveedor->tiposServicio()->sync($data['tipos_servicio']);
        }

        if (isset($data['cuentas']) && count($data['cuentas']) > 0) {
            $proveedor->cuentas()->createMany($data['cuentas']);
        }

        return new ProveedorResource($proveedor->load([
            'tipoProveedor' => fn($q) => $q->withTrashed(),
            'tipoContribuyente' => fn($q) => $q->withTrashed(),
            'tiposServicio',
            'cuentas'
        ]));
    }

    /**
     * Obtener un proveedor específico
     *
     * Devuelve los datos de un proveedor por su ID.
     */
    public function show(Proveedor $proveedor)
    {
        return new ProveedorResource($proveedor->load([
            'tipoProveedor' => fn($q) => $q->withTrashed(),
            'tipoContribuyente' => fn($q) => $q->withTrashed(),
            'tiposServicio',
            'cuentas'
        ]));
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
     * @bodyParam tipos_servicio int[] Lista de IDs de Tipos de Servicio autorizados.
     * @bodyParam cuentas object[] Lista de cuentas bancarias asociadas al proveedor.
     * @bodyParam cuentas[].id int Opcional. ID de la cuenta para actualizar una existente.
     * @bodyParam cuentas[].numero_cuenta string El número de la cuenta.
     * @bodyParam cuentas[].nombre_banco string Nombre del banco.
     * @bodyParam cuentas[].tipo_cuenta string Tipo de cuenta bancaria.
     * @bodyParam cuentas[].moneda string Moneda de la cuenta.
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
            'tipo_proveedor' => ['sometimes', 'required', Rule::exists('tipos_proveedores', 'id')->whereNull('deleted_at')],
            'id_tipo_contribuyente' => ['sometimes', 'required', Rule::exists('tipos_contribuyentes', 'id')->whereNull('deleted_at')],
            'tipos_servicio' => ['nullable', 'array'],
            'tipos_servicio.*' => [Rule::exists('tipo_servicio', 'id')->whereNull('deleted_at')],
            'cuentas' => ['nullable', 'array'],
            'cuentas.*.id' => ['nullable', 'exists:cuentas_proveedores,id'],
            'cuentas.*.numero_cuenta' => ['required', 'string', 'max:255'],
            'cuentas.*.nombre_banco' => ['required', 'string', 'max:255'],
            'cuentas.*.tipo_cuenta' => ['required', 'string', 'max:255'],
            'cuentas.*.moneda' => ['required', 'string', 'max:255'],
        ]);

        $proveedor->update($data);

        if (isset($data['tipos_servicio'])) {
            $proveedor->tiposServicio()->sync($data['tipos_servicio']);
        }

        if (isset($data['cuentas'])) {
            // Eliminar cuentas que no vinieron en la solicitud
            $cuentasIds = collect($data['cuentas'])->pluck('id')->filter()->toArray();
            $proveedor->cuentas()->whereNotIn('id', $cuentasIds)->delete();

            foreach ($data['cuentas'] as $cuentaData) {
                if (isset($cuentaData['id'])) {
                    // Update existing
                    $proveedor->cuentas()->where('id', $cuentaData['id'])->update($cuentaData);
                } else {
                    // Create new
                    $proveedor->cuentas()->create($cuentaData);
                }
            }
        }

        return new ProveedorResource($proveedor->load([
            'tipoProveedor' => fn($q) => $q->withTrashed(),
            'tipoContribuyente' => fn($q) => $q->withTrashed(),
            'tiposServicio',
            'cuentas'
        ]));
    }

    /**
     * Eliminar un proveedor
     *
     * Realiza la eliminación lógica del proveedor usando el SoftDeletes nativo de Eloquent.
     */
    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
