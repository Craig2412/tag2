<?php

namespace App\Http\Controllers;

use App\Models\PersonalEmpresa;
use App\Models\Personal;
use Illuminate\Http\Request;

class PersonalEmpresaController extends Controller
{
    /**
     * Listar vinculaciones personal-empresa
     *
     * Devuelve el listado de usuarios de tipo personal vinculados con empresas para atender sus cuentas.
     */
    public function index()
    {
        // Lista los enlaces personal-empresa y los devuelve en JSON.
        return response()->json(PersonalEmpresa::orderBy('id')->get());
    }

    /**
     * Vincular personal a una empresa
     * 
     * @bodyParam id_personal int required ID del usuario con rol personal. Ejemplo: 1
     * @bodyParam id_empresa int required ID de la empresa a vincular. Ejemplo: 1
     */
    public function store(Request $request)
    {
        // Crea un enlace personal-empresa validando el rol del usuario.
        $data = $request->validate([
            'id_personal' => ['required', 'exists:personal,id'],
            'id_empresa' => ['required', 'exists:empresas,id'],
        ]);

        $personal = Personal::find($data['id_personal']);

        if (!$personal || !$personal->usuario->hasRole('personal')) {
            return response()->json(['message' => 'id_personal debe pertenecer a un usuario con rol personal'], 422);
        }

        $item = PersonalEmpresa::create($data);

        return response()->json($item, 201);
    }

    /**
     * Obtener una vinculación personal-empresa específica
     *
     * Devuelve los datos de una vinculación por su ID.
     */
    public function show(PersonalEmpresa $personalEmpresa)
    {
        // Muestra un enlace personal-empresa por id.
        return response()->json($personalEmpresa);
    }

    /**
     * Actualizar vinculación personal-empresa
     * 
     * @bodyParam id_personal int ID del usuario personal. Ejemplo: 1
     * @bodyParam id_empresa int ID de la empresa.
     */
    public function update(Request $request, PersonalEmpresa $personalEmpresa)
    {
        // Actualiza un enlace personal-empresa y valida el rol del usuario si cambia.
        $data = $request->validate([
            'id_personal' => ['sometimes', 'required', 'exists:personal,id'],
            'id_empresa' => ['sometimes', 'required', 'exists:empresas,id'],
        ]);

        if (isset($data['id_personal'])) {
            $personal = Personal::find($data['id_personal']);
            if (!$personal || !$personal->usuario->hasRole('personal')) {
                return response()->json(['message' => 'id_personal debe pertenecer a un usuario con rol personal'], 422);
            }
        }

        $personalEmpresa->update($data);

        return response()->json($personalEmpresa);
    }

    /**
     * Eliminar vinculación personal-empresa
     */
    public function destroy(PersonalEmpresa $personalEmpresa)
    {
        // Elimina el enlace personal-empresa y confirma el resultado.
        $personalEmpresa->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
