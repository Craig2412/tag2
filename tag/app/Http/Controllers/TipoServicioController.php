<?php

namespace App\Http\Controllers;

use App\Models\TipoServicio;
use Illuminate\Http\Request;

class TipoServicioController extends Controller
{
    public function index()
    {
        $tipos = TipoServicio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($tipos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_servicio' => ['required', 'string', 'max:255'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $tipoServicio = TipoServicio::create($data);

        return response()->json($tipoServicio, 201);
    }

    public function show(TipoServicio $tipoServicio)
    {
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($tipoServicio);
    }

    public function update(Request $request, TipoServicio $tipoServicio)
    {
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'tipo_servicio' => ['sometimes', 'required', 'string', 'max:255'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $tipoServicio->update($data);

        return response()->json($tipoServicio);
    }

    public function destroy(TipoServicio $tipoServicio)
    {
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $tipoServicio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }
}
