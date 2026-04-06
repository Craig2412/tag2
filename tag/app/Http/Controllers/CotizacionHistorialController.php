<?php

namespace App\Http\Controllers;

use App\Models\CotizacionHistorial;
use Illuminate\Http\Request;

class CotizacionHistorialController extends Controller
{
    public function index()
    {
        return CotizacionHistorial::all();
    }

    public function show($id)
    {
        return CotizacionHistorial::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cotizacion_id' => 'required|exists:cotizaciones,id',
            'estatus_anterior' => 'nullable|exists:estatus,id',
            'estatus_nuevo' => 'required|exists:estatus,id',
            'usuario_id' => 'nullable|exists:users,id',
            'comentario' => 'nullable|string',
        ]);
        return CotizacionHistorial::create($data);
    }

    public function update(Request $request, $id)
    {
        $historial = CotizacionHistorial::findOrFail($id);
        $data = $request->validate([
            'estatus_anterior' => 'nullable|exists:estatus,id',
            'estatus_nuevo' => 'required|exists:estatus,id',
            'usuario_id' => 'nullable|exists:users,id',
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
