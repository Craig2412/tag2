<?php

namespace App\Http\Controllers;

use App\Models\Meta;
use App\Http\Resources\MetaResource;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    /**
     * Listar todas las metas globales
     *
     * Devuelve el listado de metas de ventas configuradas en el sistema.
     */
    public function index()
    {
        // Lista las metas y las devuelve en JSON.
        return MetaResource::collection(Meta::orderBy('id')->get());
    }

    /**
     * Crear una nueva meta global
     *
     * Registra una meta de ventas para un periodo y monto específico.
     *
     * @bodyParam monto number required Monto objetivo de la meta. Ejemplo: 50000.00
     * @bodyParam id_temporalidad int required ID de la temporalidad (Mensual, Anual, etc). Ejemplo: 1
     * @bodyParam fecha_inicio date required Fecha de inicio de la meta. Ejemplo: 2026-04-01
     * @bodyParam fecha_fin date required Fecha de fin de la meta. Ejemplo: 2026-04-30
     */
    public function store(Request $request)
    {
        // Crea una meta con datos validados y la devuelve.
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'id_temporalidad' => ['required', 'exists:temporalidades,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $item = Meta::create($data);

        return new MetaResource($item);
    }

    /**
     * Obtener una meta global específica
     *
     * Devuelve los datos de una meta por su ID.
     */
    public function show(Meta $metum)
    {
        // Muestra una meta por id (Nota: Laravel pluraliza Meta como Metae/Metum en el binding).
        return new MetaResource($metum);
    }

    /**
     * Actualizar una meta global
     *
     * Modifica los parámetros de una meta de ventas existente.
     *
     * @bodyParam monto number Monto objetivo.
     * @bodyParam id_temporalidad int ID de la temporalidad.
     * @bodyParam fecha_inicio date Fecha de inicio.
     * @bodyParam fecha_fin date Fecha de fin.
     */
    public function update(Request $request, Meta $metum)
    {
        // Actualiza una meta y devuelve el resultado.
        $data = $request->validate([
            'monto' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_temporalidad' => ['sometimes', 'required', 'exists:temporalidades,id'],
            'fecha_inicio' => ['sometimes', 'required', 'date'],
            'fecha_fin' => ['sometimes', 'required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $metum->update($data);

        return new MetaResource($metum);
    }

    /**
     * Eliminar una meta global
     *
     * Elimina permanentemente la meta del sistema.
     */
    public function destroy(Meta $metum)
    {
        // Elimina la meta y confirma el resultado.
        $metum->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
