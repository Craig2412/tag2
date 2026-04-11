<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Estatus;
use Illuminate\Http\Request;

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
                $query->with($includes);
            }
        }

        return response()->json($query->orderBy('id')->get());
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'correo_contacto' => ['nullable', 'email', 'max:255'],
            'id_tipo_contribuyente' => ['nullable', 'exists:tipos_contribuyentes,id'],
            'id_estatus' => ['nullable', 'exists:estatus,id'],
        ]);

        if (!isset($data['id_estatus'])) {
            $estatusActivo = Estatus::where('estatus', 'activo')->first();
            $data['id_estatus'] = $estatusActivo?->id;
        }

        $item = Cliente::create($data);

        return response()->json($item->load(['usuario', 'tipoContribuyente']), 201);
    }

    /**
     * Obtener un cliente específico
     */
    public function show(Cliente $cliente)
    {
        return response()->json($cliente->load(['usuario', 'tipoContribuyente']));
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
    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'usuario_id' => ['sometimes', 'nullable', 'exists:usuarios,id'],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido' => ['sometimes', 'required', 'string', 'max:255'],
            'cedula' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:255'],
            'correo_contacto' => ['sometimes', 'nullable', 'email', 'max:255'],
            'id_tipo_contribuyente' => ['sometimes', 'nullable', 'exists:tipos_contribuyentes,id'],
            'id_estatus' => ['sometimes', 'nullable', 'exists:estatus,id'],
        ]);

        $cliente->update($data);

        return response()->json($cliente->load(['usuario', 'tipoContribuyente']));
    }

    /**
     * Eliminar un cliente
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
