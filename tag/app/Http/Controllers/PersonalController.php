<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Estatus;
use App\Services\PersonalService;
use App\Http\Resources\PersonalResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonalController extends Controller
{
    /**
     * Listar todo el personal
     *
     * @queryParam include string Relaciones a incluir. Example: usuario. Values: usuario
     */
    public function index(Request $request)
    {
        $query = Personal::query();

        if ($request->has('include')) {
            $allowed = ['usuario'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (!empty($includes)) {
                $query->with(collect($includes)->mapWithKeys(function ($include) {
                    if ($include === 'usuario') {
                        return [$include => fn($q) => $q->withTrashed()];
                    }
                    return [$include => fn($q) => $q];
                })->toArray());
            }
        }

        return PersonalResource::collection($query->orderBy('id')->get());
    }

    /**
     * Crear nuevo personal
     *
     * @bodyParam usuario_id int required ID del usuario (auth). Ejemplo: 1
     * @bodyParam nombre string required Nombre. Ejemplo: Maria
     * @bodyParam apellido string required Apellido. Ejemplo: Rodriguez
     * @bodyParam cedula string Cédula. Ejemplo: 87654321
     * @bodyParam telefono string Teléfono. Ejemplo: 04241234567
     * @bodyParam correo_institucional string Correo institucional. Ejemplo: maria.rodriguez@tag.com
     * @bodyParam porcentaje_comision number Porcentaje de comisión. Ejemplo: 5.50
     * @bodyParam id_estatus int ID del estatus. Ejemplo: 1
     */
    public function store(Request $request, PersonalService $service)
    {
        $data = $request->validate([
            'usuario_id' => ['nullable', 'exists:usuarios,id'], // Cambiado a nullable para permitir creación anidada
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'correo_institucional' => ['nullable', 'email', 'max:255'],
            'porcentaje_comision' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Datos anidados del usuario
            'usuario' => ['sometimes', 'array'],
            'usuario.nombre_usuario' => ['required_with:usuario', 'string', 'max:255', 'unique:usuarios,nombre_usuario'],
            'usuario.correo' => ['required_with:usuario', 'email', 'unique:usuarios,correo'],
            'usuario.clave' => ['required_with:usuario', 'string', 'min:8'],
            'usuario.esta_activo' => ['sometimes', 'boolean'],
            'usuario.roles' => ['sometimes', 'array'],
            'usuario.roles.*' => ['exists:roles,name'],
        ]);



        try {
            $item = $service->createPersonal($data);
            return (new PersonalResource($item->load([
                'usuario' => fn($q) => $q->withTrashed()
            ])))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear personal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener un personal específico
     */
    public function show(Personal $personal)
    {
        return new PersonalResource($personal->load([
            'usuario' => fn($q) => $q->withTrashed()
        ]));
    }

    /**
     * Actualizar personal
     *
     * @bodyParam usuario_id int ID del usuario. Ejemplo: 1
     * @bodyParam nombre string Nombre. Ejemplo: Maria
     * @bodyParam apellido string Apellido. Ejemplo: Rodriguez
     * @bodyParam cedula string Cédula. Ejemplo: 87654321
     * @bodyParam telefono string Teléfono. Ejemplo: 04241234567
     * @bodyParam correo_institucional string Correo institucional. Ejemplo: maria.rodriguez@tag.com
     * @bodyParam porcentaje_comision number Porcentaje de comisión. Ejemplo: 5.50
     * @bodyParam id_estatus int ID del estatus. Ejemplo: 1
     */
    public function update(Request $request, Personal $personal, PersonalService $service)
    {
        $data = $request->validate([
            'usuario_id' => ['sometimes', 'nullable', 'exists:usuarios,id'],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido' => ['sometimes', 'required', 'string', 'max:255'],
            'cedula' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:255'],
            'correo_institucional' => ['sometimes', 'nullable', 'email', 'max:255'],
            'porcentaje_comision' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],

            // Datos anidados del usuario
            'usuario' => ['sometimes', 'array'],
            'usuario.nombre_usuario' => [
                'sometimes', 'string', 'max:255', 
                $personal->usuario_id ? Rule::unique('usuarios', 'nombre_usuario')->ignore($personal->usuario_id) : ''
            ],
            'usuario.correo' => [
                'sometimes', 'email', 
                $personal->usuario_id ? Rule::unique('usuarios', 'correo')->ignore($personal->usuario_id) : ''
            ],
            'usuario.clave' => ['sometimes', 'nullable', 'string', 'min:8'],
            'usuario.esta_activo' => ['sometimes', 'boolean'],
            'usuario.roles' => ['sometimes', 'array'],
            'usuario.roles.*' => ['exists:roles,name'],
        ]);

        try {
            $updatedPersonal = $service->updatePersonal($personal, $data);
            return new PersonalResource($updatedPersonal->load([
                'usuario' => fn($q) => $q->withTrashed()
            ]));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar personal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar personal
     */
    public function destroy(Personal $personal)
    {
        $personal->delete();
        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
