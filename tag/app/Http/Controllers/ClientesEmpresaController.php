<?php

namespace App\Http\Controllers;

use App\Models\ClienteEmpresa;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClientesEmpresaController extends Controller
{
    /**
     * Listar todas las vinculaciones cliente-empresa
     *
     * Devuelve todas las relaciones registradas entre clientes y empresas.
     */
    public function index()
    {
        // Lista los enlaces cliente-empresa y los devuelve en JSON.
        return response()->json(ClienteEmpresa::orderBy('id')->get());
    }

    /**
     * Vincular un cliente a una empresa
     *
     * Crea la asociación entre un usuario con rol cliente y una empresa del sistema.
     *
     * @bodyParam id_cliente int required ID del usuario con rol cliente. Ejemplo: 1
     * @bodyParam id_empresas int required ID de la empresa. Ejemplo: 1
     */
    public function store(Request $request)
    {
        // Crea un enlace entre cliente y empresa validando el rol.
        $data = $request->validate([
            'id_cliente' => ['required', 'exists:clientes,id'],
            'id_empresas' => ['required', 'exists:empresas,id'],
        ]);

        $cliente = Cliente::find($data['id_cliente']);

        if (!$cliente || !$cliente->usuario->hasRole('cliente')) {
            return response()->json(['message' => 'id_cliente debe pertenecer a un usuario con rol cliente'], 422);
        }

        $item = ClienteEmpresa::create($data);

        return response()->json($item, 201);
    }

    /**
     * Obtener una vinculación cliente-empresa específica
     *
     * Devuelve los datos de una vinculación por su ID.
     */
    public function show(ClienteEmpresa $clientesEmpresa)
    {
        // Muestra un enlace cliente-empresa por id.
        return response()->json($clientesEmpresa);
    }

    /**
     * Actualizar una vinculación cliente-empresa
     *
     * Modifica la relación entre un cliente y una empresa.
     *
     * @bodyParam id_cliente int ID del usuario cliente.
     * @bodyParam id_empresas int ID de la empresa.
     */
    public function update(Request $request, ClienteEmpresa $clientesEmpresa)
    {
        // Actualiza un enlace y valida el rol del cliente si cambia.
        $data = $request->validate([
            'id_cliente' => ['sometimes', 'required', 'exists:clientes,id'],
            'id_empresas' => ['sometimes', 'required', 'exists:empresas,id'],
        ]);

        if (isset($data['id_cliente'])) {
            $cliente = Cliente::find($data['id_cliente']);
            if (!$cliente || !$cliente->usuario->hasRole('cliente')) {
                return response()->json(['message' => 'id_cliente debe pertenecer a un usuario con rol cliente'], 422);
            }
        }

        $clientesEmpresa->update($data);

        return response()->json($clientesEmpresa);
    }

    /**
     * Eliminar una vinculación cliente-empresa
     *
     * Elimina permanentemente la asociación entre el cliente y la empresa.
     */
    public function destroy(ClienteEmpresa $clientesEmpresa)
    {
        // Elimina el enlace cliente-empresa y confirma el resultado.
        $clientesEmpresa->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
