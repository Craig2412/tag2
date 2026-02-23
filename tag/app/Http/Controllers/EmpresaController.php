<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    public function index()
    {
        // Lista las empresas y las devuelve en JSON.
        return response()->json(Empresa::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea una empresa con datos validados y la devuelve.
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'razon_comercial' => ['required', 'string', 'max:255'],
            'rif' => ['required', 'string', 'max:50', 'unique:empresas,rif'],
            'numero_telefono' => ['nullable', 'string', 'max:50'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'id_tipo_contribuyente' => ['required', 'exists:tipos_contribuyentes,id'],
        ]);

        $item = Empresa::create($data);

        return response()->json($item, 201);
    }

    public function show(Empresa $empresa)
    {
        // Muestra una empresa por id.
        return response()->json($empresa);
    }

    public function update(Request $request, Empresa $empresa)
    {
        // Actualiza una empresa y devuelve el resultado.
        $data = $request->validate([
            'razon_social' => ['sometimes', 'required', 'string', 'max:255'],
            'razon_comercial' => ['sometimes', 'required', 'string', 'max:255'],
            'rif' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('empresas', 'rif')->ignore($empresa->id),
            ],
            'numero_telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'correo_electronico' => ['sometimes', 'nullable', 'email', 'max:255'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'id_tipo_contribuyente' => ['sometimes', 'required', 'exists:tipos_contribuyentes,id'],
        ]);

        $empresa->update($data);

        return response()->json($empresa);
    }

    public function destroy(Empresa $empresa)
    {
        // Elimina la empresa y confirma el resultado.
        $empresa->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
