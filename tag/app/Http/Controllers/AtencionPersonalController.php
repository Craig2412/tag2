<?php

namespace App\Http\Controllers;

use App\Models\AtencionPersonal;
use App\Models\User;
use Illuminate\Http\Request;

class AtencionPersonalController extends Controller
{
    public function index()
    {
        // Lista los enlaces atencion-personal y los devuelve en JSON.
        return response()->json(AtencionPersonal::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea un enlace entre atencion y personal validando el rol.
        $data = $request->validate([
            'id_atencion' => ['required', 'exists:atenciones,id'],
            'id_personal' => ['required', 'exists:users,id'],
        ]);

        $personal = User::find($data['id_personal']);

        if (!$personal || !$personal->hasRole('personal')) {
            return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
        }

        $item = AtencionPersonal::create($data);

        return response()->json($item, 201);
    }

    public function show(AtencionPersonal $atencionPersonal)
    {
        // Muestra un enlace atencion-personal por id.
        return response()->json($atencionPersonal);
    }

    public function update(Request $request, AtencionPersonal $atencionPersonal)
    {
        // Actualiza un enlace y valida el rol del personal si cambia.
        $data = $request->validate([
            'id_atencion' => ['sometimes', 'required', 'exists:atenciones,id'],
            'id_personal' => ['sometimes', 'required', 'exists:users,id'],
        ]);

        if (isset($data['id_personal'])) {
            $personal = User::find($data['id_personal']);
            if (!$personal || !$personal->hasRole('personal')) {
                return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
            }
        }

        $atencionPersonal->update($data);

        return response()->json($atencionPersonal);
    }

    public function destroy(AtencionPersonal $atencionPersonal)
    {
        // Elimina el enlace atencion-personal y confirma el resultado.
        $atencionPersonal->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
