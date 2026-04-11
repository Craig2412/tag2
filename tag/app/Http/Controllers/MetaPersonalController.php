<?php

namespace App\Http\Controllers;

use App\Models\MetaPersonal;
use App\Models\Personal;
use Illuminate\Http\Request;

class MetaPersonalController extends Controller
{
    /**
     * Listar asignaciones de metas personales
     *
     * Devuelve el listado de metas asignadas individualmente al personal.
     */
    public function index()
    {
        // Lista las metas personales y las devuelve en JSON.
        return response()->json(MetaPersonal::orderBy('id')->get());
    }

    /**
     * Asignar meta a personal
     *
     * Registra una meta de ventas específica para un usuario con rol personal.
     *
     * @bodyParam id_personal int required ID del usuario con rol personal. Ejemplo: 1
     * @bodyParam monto number required Monto objetivo de la meta personal. Ejemplo: 10000.00
     * @bodyParam id_temporalidad int required ID de la temporalidad. Ejemplo: 1
     * @bodyParam fecha_inicio date required Fecha de inicio. Ejemplo: 2026-04-01
     * @bodyParam fecha_fin date required Fecha de fin. Ejemplo: 2026-04-30
     */
    public function store(Request $request)
    {
        // Asigna una meta a un personal validando su rol.
        $data = $request->validate([
            'id_personal' => ['required', 'exists:personal,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'id_temporalidad' => ['required', 'exists:temporalidades,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $personal = Personal::find($data['id_personal']);

        if (!$personal || !$personal->usuario->hasRole('personal')) {
            return response()->json(['message' => 'id_personal debe pertenecer a un usuario con rol personal'], 422);
        }

        $item = MetaPersonal::create($data);

        return response()->json($item, 201);
    }

    /**
     * Obtener una meta personal específica
     *
     * Devuelve los datos de una asignación de meta personal por su ID.
     */
    public function show(MetaPersonal $metaPersonal)
    {
        // Muestra una meta personal por id.
        return response()->json($metaPersonal);
    }

    /**
     * Actualizar asignación de meta personal
     *
     * Modifica los datos de una meta personal ya asignada.
     *
     * @bodyParam id_personal int ID del usuario personal. Ejemplo: 1
     * @bodyParam monto number Monto objetivo.
     * @bodyParam id_temporalidad int ID de la temporalidad.
     * @bodyParam fecha_inicio date Fecha de inicio.
     * @bodyParam fecha_fin date Fecha de fin.
     */
    public function update(Request $request, MetaPersonal $metaPersonal)
    {
        // Actualiza una meta personal y valida el rol si el usuario cambia.
        $data = $request->validate([
            'id_personal' => ['sometimes', 'required', 'exists:personal,id'],
            'monto' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_temporalidad' => ['sometimes', 'required', 'exists:temporalidades,id'],
            'fecha_inicio' => ['sometimes', 'required', 'date'],
            'fecha_fin' => ['sometimes', 'required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        if (isset($data['id_personal'])) {
            $personal = Personal::find($data['id_personal']);
            if (!$personal || !$personal->usuario->hasRole('personal')) {
                return response()->json(['message' => 'id_personal debe pertenecer a un usuario con rol personal'], 422);
            }
        }

        $metaPersonal->update($data);

        return response()->json($metaPersonal);
    }

    /**
     * Eliminar meta personal
     *
     * Elimina permanentemente la asignación de meta personal.
     */
    public function destroy(MetaPersonal $metaPersonal)
    {
        // Elimina la meta personal y confirma el resultado.
        $metaPersonal->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
