<?php

namespace App\Http\Controllers;

use App\Models\TipoServicio;
use Illuminate\Http\Request;

class TipoServicioController extends Controller
{
    public function index()
    {
        // Lista los tipos de servicio activos y los devuelve en JSON.
        $tipos = TipoServicio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($tipos);
    }

    public function store(Request $request)
    {
        // Crea un tipo de servicio con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_servicio' => ['required', 'string', 'max:255'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'iva_defecto' => ['nullable', 'numeric'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $tipoServicio = TipoServicio::create($data);

        return response()->json($tipoServicio, 201);
    }

    public function show(TipoServicio $tipoServicio)
    {
        // Muestra un tipo de servicio si no esta marcado como borrado.
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($tipoServicio);
    }

    public function update(Request $request, TipoServicio $tipoServicio)
    {
        // Actualiza un tipo de servicio activo y devuelve el resultado.
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'tipo_servicio' => ['sometimes', 'required', 'string', 'max:255'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'iva_defecto' => ['nullable', 'numeric'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $tipoServicio->update($data);

        return response()->json($tipoServicio);
    }

    public function destroy(TipoServicio $tipoServicio)
    {
        // Marca el tipo de servicio como borrado logico.
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $tipoServicio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }
}
