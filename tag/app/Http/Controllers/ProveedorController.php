<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index()
    {
        // Lista los proveedores activos y los devuelve en JSON.
        $proveedores = Proveedor::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($proveedores);
    }

    public function store(Request $request)
    {
        // Crea un proveedor con datos validados y lo devuelve.
        $data = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:255'],
            'razon_comercial' => ['required', 'string', 'max:255'],
            'rif' => ['required', 'string', 'max:50', 'unique:proveedores,rif'],
            'correo_empresa' => ['required', 'email', 'max:255', 'unique:proveedores,correo_empresa'],
            'telefono_empresa' => ['nullable', 'string', 'max:50'],
            'nombre_persona_contacto' => ['required', 'string', 'max:255'],
            'tipo_proveedor' => ['required', 'exists:tipos_proveedores,id'],
            'estatus' => ['required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $proveedor = Proveedor::create($data);

        return response()->json($proveedor, 201);
    }

    public function show(Proveedor $proveedor)
    {
        // Muestra un proveedor si no esta marcado como borrado.
        if ($proveedor->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($proveedor);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        // Actualiza un proveedor activo y devuelve el resultado.
        if ($proveedor->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'nombre_empresa' => ['sometimes', 'required', 'string', 'max:255'],
            'razon_comercial' => ['sometimes', 'required', 'string', 'max:255'],
            'rif' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('proveedores', 'rif')->ignore($proveedor->id),
            ],
            'correo_empresa' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores', 'correo_empresa')->ignore($proveedor->id),
            ],
            'telefono_empresa' => ['sometimes', 'nullable', 'string', 'max:50'],
            'nombre_persona_contacto' => ['sometimes', 'required', 'string', 'max:255'],
            'tipo_proveedor' => ['sometimes', 'required', 'exists:tipos_proveedores,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $proveedor->update($data);

        return response()->json($proveedor);
    }

    public function destroy(Proveedor $proveedor)
    {
        // Marca el proveedor como borrado logico.
        if ($proveedor->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $proveedor->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }
}
