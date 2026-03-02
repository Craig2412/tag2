<?php

namespace App\Http\Controllers;

use App\Models\MetaPersonal;
use App\Models\User;
use Illuminate\Http\Request;

class MetaPersonalController extends Controller
{
    public function index()
    {
        return response()->json(MetaPersonal::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_meta' => ['required', 'exists:metas,id'],
            'id_personal' => ['required', 'exists:users,id'],
        ]);

        $personal = User::find($data['id_personal']);
        if (!$personal || !$personal->hasRole('personal')) {
            return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
        }

        $duplicado = MetaPersonal::where('id_meta', $data['id_meta'])
            ->where('id_personal', $data['id_personal'])
            ->exists();

        if ($duplicado) {
            return response()->json(['message' => 'La combinación id_meta + id_personal ya existe'], 422);
        }

        $item = MetaPersonal::create($data);

        return response()->json($item, 201);
    }

    public function show(MetaPersonal $metaPersonal)
    {
        return response()->json($metaPersonal);
    }

    public function update(Request $request, MetaPersonal $metaPersonal)
    {
        $data = $request->validate([
            'id_meta' => ['sometimes', 'required', 'exists:metas,id'],
            'id_personal' => ['sometimes', 'required', 'exists:users,id'],
        ]);

        if (isset($data['id_personal'])) {
            $personal = User::find($data['id_personal']);
            if (!$personal || !$personal->hasRole('personal')) {
                return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
            }
        }

        $idMeta = $data['id_meta'] ?? $metaPersonal->id_meta;
        $idPersonal = $data['id_personal'] ?? $metaPersonal->id_personal;

        $duplicado = MetaPersonal::where('id_meta', $idMeta)
            ->where('id_personal', $idPersonal)
            ->where('id', '!=', $metaPersonal->id)
            ->exists();

        if ($duplicado) {
            return response()->json(['message' => 'La combinación id_meta + id_personal ya existe'], 422);
        }

        $metaPersonal->update($data);

        return response()->json($metaPersonal);
    }

    public function destroy(MetaPersonal $metaPersonal)
    {
        $metaPersonal->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
