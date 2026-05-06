<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Http\Resources\EmpresaResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    /**
     * Listar todas las empresas
     *
     * Devuelve el listado completo de empresas registradas en el sistema.
     */
    public function index(Request $request)
    {
        $query = Empresa::query();

        if ($request->has('include')) {
            $allowed = ['tipoContribuyente'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (!empty($includes)) {
                $query->with(collect($includes)->mapWithKeys(function ($include) {
                    if ($include === 'tipoContribuyente') {
                        return [$include => fn($q) => $q->withTrashed()];
                    }
                    return [$include => fn($q) => $q];
                })->toArray());
            }
        }

        return EmpresaResource::collection($query->orderBy('id')->get());
    }

    /**
     * Crear una nueva empresa
     *
     * Registra una nueva empresa cliente en el sistema.
     *
     * @bodyParam razon_social string required Razón social de la empresa. Ejemplo: Corporación Delta C.A.
     * @bodyParam razon_comercial string required Nombre comercial. Ejemplo: Delta Tech
     * @bodyParam rif string required RIF de la empresa. Ejemplo: J-12345678-0
     * @bodyParam numero_telefono string Teléfono de contacto. Ejemplo: +58 212 555 1234
     * @bodyParam correo_electronico string Correo electrónico de contacto. Ejemplo: contacto@delta.com
     * @bodyParam direccion string Dirección física de la empresa. Ejemplo: Av. Las Mercedes, Caracas
     * @bodyParam id_tipo_contribuyente int required ID del tipo de contribuyente. Ejemplo: 1
     */
    public function store(Request $request)
    {
        // Crea una empresa con datos validados y la devuelve.
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'razon_comercial' => ['required', 'string', 'max:255'],
            'rif' => ['required', 'string', 'max:50', 'unique:empresas,rif'],
            'numero_telefono' => ['nullable', 'string', 'max:50'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'id_tipo_contribuyente' => ['required', Rule::exists('tipos_contribuyentes', 'id')->whereNull('deleted_at')],
        ]);

        $item = Empresa::create($data);
        $item->load(['tipoContribuyente' => fn($q) => $q->withTrashed()]);

        return new EmpresaResource($item);
    }

    /**
     * Obtener una empresa específica
     *
     * Devuelve los datos de una empresa por su ID.
     */
    public function show(Empresa $empresa)
    {
        // Muestra una empresa por id.
        return new EmpresaResource($empresa->load(['tipoContribuyente' => fn($q) => $q->withTrashed()]));
    }

    /**
     * Actualizar una empresa existente
     *
     * Modifica los datos de una empresa registrada en el sistema.
     *
     * @bodyParam razon_social string Razón social de la empresa.
     * @bodyParam razon_comercial string Nombre comercial.
     * @bodyParam rif string RIF de la empresa.
     * @bodyParam numero_telefono string Teléfono de contacto.
     * @bodyParam correo_electronico string Correo electrónico de contacto.
     * @bodyParam direccion string Dirección física de la empresa.
     * @bodyParam id_tipo_contribuyente int ID del tipo de contribuyente.
     */
    public function update(Request $request, Empresa $empresa)
    {
        // Actualiza una empresa y devuelve el resultado.
        $data = $request->validate([
            'razon_social' => ['sometimes', 'required', 'string', 'max:255'],
            'razon_comercial' => ['sometimes', 'required', 'string', 'max:255'],
            'rif' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('empresas', 'rif')->ignore($empresa->id),
            ],
            'numero_telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'correo_electronico' => ['sometimes', 'nullable', 'email', 'max:255'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'id_tipo_contribuyente' => ['sometimes', 'required', Rule::exists('tipos_contribuyentes', 'id')->whereNull('deleted_at')],
        ]);

        $empresa->update($data);
        $empresa->load(['tipoContribuyente' => fn($q) => $q->withTrashed()]);

        return new EmpresaResource($empresa);
    }

    /**
     * Eliminar una empresa
     *
     * Elimina permanentemente la empresa del sistema.
     */
    public function destroy(Empresa $empresa)
    {
        // Elimina la empresa y confirma el resultado.
        $empresa->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
