<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Estatus;
use App\Services\ClienteService;
use App\Http\Resources\ClienteResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Listar todos los clientes
     *
     * @queryParam include string Relaciones a incluir. Example: usuario. Values: usuario, tipoContribuyente
     */
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($request->has('include')) {
            $allowed = ['usuario', 'tipoContribuyente'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (!empty($includes)) {
                $query->with(collect($includes)->mapWithKeys(function ($include) {
                    if ($include === 'tipoContribuyente' || $include === 'usuario') {
                        return [$include => fn($q) => $q->withTrashed()];
                    }
                    return [$include => fn($q) => $q];
                })->toArray());
            }
        }

        return ClienteResource::collection($query->orderBy('id')->get());
    }

    /**
     * Crear un nuevo cliente
     *
     * @bodyParam usuario_id int required ID del usuario (auth). Ejemplo: 1
     * @bodyParam nombre string required Nombre. Ejemplo: Juan
     * @bodyParam apellido string required Apellido. Ejemplo: Perez
     * @bodyParam cedula string Cédula. Ejemplo: 12345678
     * @bodyParam telefono string Teléfono. Ejemplo: 04121234567
     * @bodyParam correo_contacto string Correo de contacto. Ejemplo: juan.perez@email.com
     * @bodyParam id_tipo_contribuyente int ID tipo contribuyente. Ejemplo: 1
     * @bodyParam id_estatus int ID estatus. Ejemplo: 1
     */
    public function store(Request $request, ClienteService $service)
    {
        $data = $request->validate([
            'usuario_id' => ['nullable', Rule::exists('usuarios', 'id')->whereNull('deleted_at')],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'correo_contacto' => ['nullable', 'email', 'max:255'],
            'id_tipo_contribuyente' => ['nullable', Rule::exists('tipos_contribuyentes', 'id')->whereNull('deleted_at')],

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
            $item = $service->createCliente($data);
            return (new ClienteResource($item->load([
                'usuario' => fn($q) => $q->withTrashed(), 
                'tipoContribuyente' => fn($q) => $q->withTrashed()
            ])))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear cliente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener un cliente específico
     */
    public function show(Cliente $cliente)
    {
        return new ClienteResource($cliente->load([
            'usuario' => fn($q) => $q->withTrashed(), 
            'tipoContribuyente' => fn($q) => $q->withTrashed()
        ]));
    }

    /**
     * Actualizar un cliente existente
     *
     * @bodyParam usuario_id int ID del usuario (auth). Ejemplo: 1
     * @bodyParam nombre string Nombre. Ejemplo: Juan
     * @bodyParam apellido string Apellido. Ejemplo: Perez
     * @bodyParam cedula string Cédula. Ejemplo: 12345678
     * @bodyParam telefono string Teléfono. Ejemplo: 04121234567
     * @bodyParam correo_contacto string Correo de contacto. Ejemplo: juan.perez@email.com
     * @bodyParam id_tipo_contribuyente int ID tipo contribuyente. Ejemplo: 1
     * @bodyParam id_estatus int ID estatus. Ejemplo: 1
     */
    public function update(Request $request, Cliente $cliente, ClienteService $service)
    {
        $data = $request->validate([
            'usuario_id' => ['sometimes', 'nullable', Rule::exists('usuarios', 'id')->whereNull('deleted_at')],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido' => ['sometimes', 'required', 'string', 'max:255'],
            'cedula' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:255'],
            'correo_contacto' => ['sometimes', 'nullable', 'email', 'max:255'],
            'id_tipo_contribuyente' => ['sometimes', 'nullable', Rule::exists('tipos_contribuyentes', 'id')->whereNull('deleted_at')],

            // Datos anidados del usuario
            'usuario' => ['sometimes', 'array'],
            'usuario.nombre_usuario' => [
                'sometimes', 'string', 'max:255', 
                $cliente->usuario_id ? Rule::unique('usuarios', 'nombre_usuario')->ignore($cliente->usuario_id) : ''
            ],
            'usuario.correo' => [
                'sometimes', 'email', 
                $cliente->usuario_id ? Rule::unique('usuarios', 'correo')->ignore($cliente->usuario_id) : ''
            ],
            'usuario.clave' => ['sometimes', 'nullable', 'string', 'min:8'],
            'usuario.esta_activo' => ['sometimes', 'boolean'],
            'usuario.roles' => ['sometimes', 'array'],
            'usuario.roles.*' => ['exists:roles,name'],
        ]);

        try {
            $updatedCliente = $service->updateCliente($cliente, $data);
            return new ClienteResource($updatedCliente->load([
                'usuario' => fn($q) => $q->withTrashed(), 
                'tipoContribuyente' => fn($q) => $q->withTrashed()
            ]));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar cliente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar un cliente
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
