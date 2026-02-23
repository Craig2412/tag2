<?php

namespace App\Http\Controllers;

use App\Models\ClienteEmpresa;
use App\Models\User;
use Illuminate\Http\Request;

class ClientesEmpresaController extends Controller
{
    public function index()
    {
        // Lista los enlaces cliente-empresa y los devuelve en JSON.
        return response()->json(ClienteEmpresa::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un enlace entre cliente y empresa validando el rol.
        $data = $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'id_empresas' => ['required', 'exists:empresas,id'],
        ]);

        $cliente = User::find($data['id_cliente']);

        if (!$cliente || !$cliente->hasRole('cliente')) {
            return response()->json(['message' => 'id_cliente debe ser un usuario con rol cliente'], 422);
        }

        $item = ClienteEmpresa::create($data);

        return response()->json($item, 201);
    }

    public function show(ClienteEmpresa $clientesEmpresa)
    {
        // Muestra un enlace cliente-empresa por id.
        return response()->json($clientesEmpresa);
    }

    public function update(Request $request, ClienteEmpresa $clientesEmpresa)
    {
        // Actualiza un enlace y valida el rol del cliente si cambia.
        $data = $request->validate([
            'id_cliente' => ['sometimes', 'required', 'exists:users,id'],
            'id_empresas' => ['sometimes', 'required', 'exists:empresas,id'],
        ]);

        if (isset($data['id_cliente'])) {
            $cliente = User::find($data['id_cliente']);
            if (!$cliente || !$cliente->hasRole('cliente')) {
                return response()->json(['message' => 'id_cliente debe ser un usuario con rol cliente'], 422);
            }
        }

        $clientesEmpresa->update($data);

        return response()->json($clientesEmpresa);
    }

    public function destroy(ClienteEmpresa $clientesEmpresa)
    {
        // Elimina el enlace cliente-empresa y confirma el resultado.
        $clientesEmpresa->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
