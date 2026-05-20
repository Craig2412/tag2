<?php

namespace App\Http\Controllers;

use App\Http\Resources\EtapaComercialResource;
use App\Models\EtapaComercial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EtapaComercialController extends Controller
{
    /**
     * Listar todas las etapas comerciales
     *
     * Devuelve el catálogo de etapas comerciales (atención, cotizada, orden_compra).
     */
    public function index()
    {
        return EtapaComercialResource::collection(EtapaComercial::orderBy('id')->get());
    }

    /**
     * Crear una nueva etapa comercial
     *
     * @bodyParam slug string required Slug único. Ejemplo: facturada
     * @bodyParam label string required Etiqueta descriptiva. Ejemplo: Facturada
     * @bodyParam color string Código de color hexadecimal. Ejemplo: #8B5CF6
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:etapas_comerciales,slug'],
            'label' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $etapa = EtapaComercial::create($data);

        return new EtapaComercialResource($etapa);
    }

    /**
     * Obtener una etapa comercial específica
     */
    public function show(EtapaComercial $etapaComercial)
    {
        return new EtapaComercialResource($etapaComercial);
    }

    /**
     * Actualizar una etapa comercial
     *
     * @bodyParam slug string Slug único. Ejemplo: facturada
     * @bodyParam label string Etiqueta descriptiva. Ejemplo: Facturada
     * @bodyParam color string Código de color hexadecimal. Ejemplo: #8B5CF6
     */
    public function update(Request $request, EtapaComercial $etapaComercial)
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('etapas_comerciales', 'slug')->ignore($etapaComercial->id)],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $etapaComercial->update($data);

        return new EtapaComercialResource($etapaComercial);
    }

    /**
     * Eliminar una etapa comercial (soft delete)
     */
    public function destroy(EtapaComercial $etapaComercial)
    {
        $etapaComercial->delete();

        return response()->json(['data' => ['message' => 'Eliminada correctamente']]);
    }
}
