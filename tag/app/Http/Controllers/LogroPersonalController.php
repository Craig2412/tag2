<?php

namespace App\Http\Controllers;

use App\Models\LogroPersonal;
use Illuminate\Http\Request;

class LogroPersonalController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'id_personal' => ['sometimes', 'integer', 'exists:users,id'],
            'tipo_entidad' => ['sometimes', 'in:atencion,cotizacion,orden_compra'],
            'id_entidad' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $query = LogroPersonal::query()
            ->with(['personal', 'estatusAnterior', 'estatusNuevo'])
            ->orderByDesc('id');

        if (isset($data['id_personal'])) {
            $query->where('id_personal', $data['id_personal']);
        }

        if (isset($data['tipo_entidad'])) {
            $query->where('tipo_entidad', $data['tipo_entidad']);
        }

        if (isset($data['id_entidad'])) {
            $query->where('id_entidad', $data['id_entidad']);
        }

        $perPage = $data['per_page'] ?? 50;

        return response()->json($query->paginate($perPage));
    }
}
