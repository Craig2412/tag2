<?php

namespace App\Http\Controllers;

use App\Models\PersonalEmpresa;
use App\Models\User;
use Illuminate\Http\Request;

class PersonalEmpresaController extends Controller
{
    public function index()
    {
        // Lista los enlaces personal-empresa y los devuelve en JSON.
        return response()->json(PersonalEmpresa::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un enlace personal-empresa validando el rol.
        $data = $request->validate([
            'id_personal' => ['required', 'exists:users,id'],
            'id_empresa' => ['required', 'exists:empresas,id'],
        ]);

        $personal = User::find($data['id_personal']);

        if (!$personal || !$personal->hasRole('personal')) {
            return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
        }

        $item = PersonalEmpresa::create($data);

        return response()->json($item, 201);
    }

    public function show(PersonalEmpresa $personalEmpresa)
    {
        // Muestra un enlace personal-empresa por id.
        return response()->json($personalEmpresa);
    }

    public function update(Request $request, PersonalEmpresa $personalEmpresa)
    {
        // Actualiza un enlace personal-empresa y valida el rol si cambia.
        $data = $request->validate([
            'id_personal' => ['sometimes', 'required', 'exists:users,id'],
            'id_empresa' => ['sometimes', 'required', 'exists:empresas,id'],
        ]);

        if (isset($data['id_personal'])) {
            $personal = User::find($data['id_personal']);
            if (!$personal || !$personal->hasRole('personal')) {
                return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
            }
        }

        $personalEmpresa->update($data);

        return response()->json($personalEmpresa);
    }

    public function destroy(PersonalEmpresa $personalEmpresa)
    {
        // Elimina el enlace personal-empresa y confirma el resultado.
        $personalEmpresa->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
