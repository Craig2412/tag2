<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\Estatus;
use App\Models\User;
use Illuminate\Http\Request;

class AtencionController extends Controller
{
    public function index()
    {
        // Lista las atenciones activas y las devuelve en JSON.
        $items = Atencion::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        // Crea una atencion, valida roles y asigna estatus inicial.
        $data = $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'id_personal' => ['required', 'exists:users,id'],
            'id_origen_atencion' => ['required', 'exists:origenes,id'],
            'asunto' => ['required', 'string', 'max:255'],
            'notas_adicionales' => ['nullable', 'string'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $cliente = User::find($data['id_cliente']);
        $personal = User::find($data['id_personal']);

        if (!$cliente || !$cliente->hasRole('cliente')) {
            return response()->json(['message' => 'id_cliente debe ser un usuario con rol cliente'], 422);
        }

        if (!$personal || !$personal->hasRole('personal')) {
            return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
        }

        $estatus = Estatus::firstOrCreate(['estatus' => 'por aprobar']);

        $data['estatus'] = $estatus->id;
        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $item = Atencion::create($data);

        return response()->json($item, 201);
    }

    public function show(Atencion $atencion)
    {
        // Muestra una atencion si no esta marcada como borrada.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($atencion);
    }

    public function update(Request $request, Atencion $atencion)
    {
        // Actualiza una atencion activa y valida roles cuando cambian.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'id_cliente' => ['sometimes', 'required', 'exists:users,id'],
            'id_personal' => ['sometimes', 'required', 'exists:users,id'],
            'id_origen_atencion' => ['sometimes', 'required', 'exists:origenes,id'],
            'asunto' => ['sometimes', 'required', 'string', 'max:255'],
            'notas_adicionales' => ['sometimes', 'nullable', 'string'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['id_cliente'])) {
            $cliente = User::find($data['id_cliente']);
            if (!$cliente || !$cliente->hasRole('cliente')) {
                return response()->json(['message' => 'id_cliente debe ser un usuario con rol cliente'], 422);
            }
        }

        if (isset($data['id_personal'])) {
            $personal = User::find($data['id_personal']);
            if (!$personal || !$personal->hasRole('personal')) {
                return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
            }
        }

        $atencion->update($data);

        return response()->json($atencion);
    }

    public function destroy(Atencion $atencion)
    {
        // Marca la atencion como borrada de forma logica.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $atencion->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }
}
