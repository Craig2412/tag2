<?php

namespace App\Http\Controllers;

use App\Models\AtencionHistorial;
use Illuminate\Http\Request;

class AtencionHistorialController extends Controller
{
    public function index()
    {
        return AtencionHistorial::all();
    }

    public function show($id)
    {
        return AtencionHistorial::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'atencion_id' => 'required|exists:atenciones,id',
            'estatus_anterior' => 'nullable|exists:estatus,id',
            'estatus_nuevo' => 'required|exists:estatus,id',
            'usuario_id' => 'nullable|exists:usuarios,id',
            'comentario' => 'nullable|string',
        ]);
        return AtencionHistorial::create($data);
    }

    public function update(Request $request, $id)
    {
        $historial = AtencionHistorial::findOrFail($id);
        $data = $request->validate([
            'estatus_anterior' => 'nullable|exists:estatus,id',
            'estatus_nuevo' => 'required|exists:estatus,id',
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
