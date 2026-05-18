<?php

namespace App\Http\Controllers;

use App\Models\CotizacionHistorial;
use Illuminate\Http\Request;

class CotizacionHistorialController extends Controller
{
    public function index()
    {
        return CotizacionHistorial::with([
            'usuario' => fn($q) => $q->withTrashed(),
            'cotizacion' => fn($q) => $q->withTrashed(),
            'estatusAnteriorObj' => fn($q) => $q->withTrashed(),
            'estatusNuevoObj' => fn($q) => $q->withTrashed()
        ])->get();
    }

    public function show($id)
    {
        return CotizacionHistorial::with([
            'usuario' => fn($q) => $q->withTrashed(),
            'cotizacion' => fn($q) => $q->withTrashed(),
            'estatusAnteriorObj' => fn($q) => $q->withTrashed(),
            'estatusNuevoObj' => fn($q) => $q->withTrashed()
        ])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cotizacion_id' => 'required|exists:cotizaciones,id',
            'id_estado_anterior' => 'nullable|exists:estados_cotizaciones,id',
            'id_estado_nuevo' => 'required|exists:estados_cotizaciones,id',
            'usuario_id' => 'nullable|exists:usuarios,id',
            'comentario' => 'nullable|string',
        ]);
        return CotizacionHistorial::create($data);
    }

    public function update(Request $request, $id)
    {
        $historial = CotizacionHistorial::findOrFail($id);
        $data = $request->validate([
            'id_estado_anterior' => 'nullable|exists:estados_cotizaciones,id',
            'id_estado_nuevo' => 'required|exists:estados_cotizaciones,id',
            'usuario_id' => 'nullable|exists:usuarios,id',
            'comentario' => 'nullable|string',
        ]);
        $historial->update($data);
        return $historial;
    }

    public function destroy($id)
    {
        $historial = CotizacionHistorial::findOrFail($id);
        $historial->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
