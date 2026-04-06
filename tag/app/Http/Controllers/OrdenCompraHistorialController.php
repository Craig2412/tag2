<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompraHistorial;
use Illuminate\Http\Request;

class OrdenCompraHistorialController extends Controller
{
    public function index()
    {
        return OrdenCompraHistorial::all();
    }

    public function show($id)
    {
        return OrdenCompraHistorial::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'orden_compra_id' => 'required|exists:ordenes_compra,id',
            'estatus_anterior' => 'nullable|exists:estatus,id',
            'estatus_nuevo' => 'required|exists:estatus,id',
            'usuario_id' => 'nullable|exists:users,id',
            'comentario' => 'nullable|string',
        ]);
        return OrdenCompraHistorial::create($data);
    }

    public function update(Request $request, $id)
    {
        $historial = OrdenCompraHistorial::findOrFail($id);
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
        $historial = OrdenCompraHistorial::findOrFail($id);
        $historial->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
