<?php

namespace App\Http\Controllers;

use App\Models\AtencionHistorial;
use Illuminate\Http\Request;

class AtencionHistorialController extends Controller
{
    public function index()
    {
        return AtencionHistorial::with([
            'usuario' => fn ($q) => $q->withTrashed(),
            'atencion' => fn ($q) => $q->withTrashed(),
            'estatusAnteriorObj' => fn ($q) => $q->withTrashed(),
            'estatusNuevoObj' => fn ($q) => $q->withTrashed(),
        ])->get();
    }

    public function show($id)
    {
        return AtencionHistorial::with([
            'usuario' => fn ($q) => $q->withTrashed(),
            'atencion' => fn ($q) => $q->withTrashed(),
            'estatusAnteriorObj' => fn ($q) => $q->withTrashed(),
            'estatusNuevoObj' => fn ($q) => $q->withTrashed(),
        ])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'atencion_id' => 'required|exists:atenciones,id',
            'id_estado_anterior' => 'nullable|exists:estados_atenciones,id',
            'id_estado_nuevo' => 'nullable|exists:estados_atenciones,id',
            'id_etapa_anterior' => 'nullable|exists:etapas_comerciales,id',
            'id_etapa_nueva' => 'nullable|exists:etapas_comerciales,id',
            'usuario_id' => 'nullable|exists:usuarios,id',
            'comentario' => 'nullable|string',
        ]);

        return AtencionHistorial::create($data);
    }

    public function update(Request $request, $id)
    {
        $historial = AtencionHistorial::findOrFail($id);
        $data = $request->validate([
            'id_estado_anterior' => 'nullable|exists:estados_atenciones,id',
            'id_estado_nuevo' => 'nullable|exists:estados_atenciones,id',
            'id_etapa_anterior' => 'nullable|exists:etapas_comerciales,id',
            'id_etapa_nueva' => 'nullable|exists:etapas_comerciales,id',
            'usuario_id' => 'nullable|exists:usuarios,id',
            'comentario' => 'nullable|string',
        ]);
        $historial->update($data);

        return $historial;
    }

    public function destroy($id)
    {
        $historial = AtencionHistorial::findOrFail($id);
        $historial->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
