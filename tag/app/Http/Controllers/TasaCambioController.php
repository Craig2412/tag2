<?php

namespace App\Http\Controllers;

use App\Models\TasaCambio;
use App\Models\Tasa;
use App\Http\Resources\TasaCambioResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TasaCambioController extends Controller
{
    /**
     * Listar todas las tasas de cambio
     *
     * Devuelve el historial de tasas de cambio registradas.
     */
    public function index(Request $request)
    {
        $query = TasaCambio::query();

        if ($request->has('include')) {
            $allowed = ['monedaCatalogo'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (!empty($includes)) {
                $query->with($includes);
            }
        }

        $tasas = $query->orderByDesc('fecha')
            ->orderBy('id_tasa')
            ->get();

        return TasaCambioResource::collection($tasas);
    }

    /**
     * Registrar una nueva tasa de cambio
     *
     * @bodyParam id_tasa int required ID de la moneda del catálogo (Ej: USD_BCV). Ejemplo: 1
     * @bodyParam valor_cambio number required Valor numérico de la tasa. Ejemplo: 36.50
     * @bodyParam fecha date required Fecha de la tasa. Ejemplo: 2026-04-10
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_tasa' => ['required', 'exists:tasas,id'],
            'valor_cambio' => ['required', 'numeric', 'min:0.0001'],
            'fecha' => ['required', 'date'],
        ]);

        // Evitar duplicados para la misma moneda y fecha
        $existe = TasaCambio::where('id_tasa', $data['id_tasa'])
            ->whereDate('fecha', $data['fecha'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Ya existe un registro para esta moneda en la fecha indicada',
            ], 422);
        }

        $tasa = TasaCambio::create($data);

        return new TasaCambioResource($tasa->load('monedaCatalogo'));
    }

    /**
     * Obtener una tasa de cambio específica
     */
    public function show(TasaCambio $tasaCambio)
    {
        return new TasaCambioResource($tasaCambio->load('monedaCatalogo'));
    }

    /**
     * Actualizar una tasa de cambio
     *
     * @bodyParam id_tasa int ID de la moneda. Ejemplo: 1
     * @bodyParam valor_cambio number Valor de la tasa. Ejemplo: 36.55
     * @bodyParam fecha date Fecha. Ejemplo: 2026-04-11
     */
    public function update(Request $request, TasaCambio $tasaCambio)
    {
        $data = $request->validate([
            'id_tasa' => ['sometimes', 'required', 'exists:tasas,id'],
            'valor_cambio' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'fecha' => ['sometimes', 'required', 'date'],
        ]);

        if (isset($data['id_tasa']) || isset($data['fecha'])) {
            $idTasa = $data['id_tasa'] ?? $tasaCambio->id_tasa;
            $fecha = $data['fecha'] ?? $tasaCambio->fecha;

            $existe = TasaCambio::where('id_tasa', $idTasa)
                ->whereDate('fecha', $fecha)
                ->where('id', '!=', $tasaCambio->id)
                ->exists();

            if ($existe) {
                return response()->json([
                    'message' => 'Ya existe otro registro para esta moneda en la fecha indicada',
                ], 422);
            }
        }

        $tasaCambio->update($data);

        return new TasaCambioResource($tasaCambio->load('monedaCatalogo'));
    }

    /**
     * Eliminar una tasa de cambio
     */
    public function destroy(TasaCambio $tasaCambio)
    {
        $tasaCambio->delete();
        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }

    /**
     * Obtener las últimas tasas vigentes
     *
     * Útil para el frontend para mostrar la tasa actual de cada moneda.
     */
    public function vigentes()
    {
        $tasasVigentes = Tasa::with(['tasasHistorial' => function($query) {
            $query->latest('fecha')->latest('id');
        }])->get()->map(function($tasa) {
            return [
                'moneda' => $tasa->codigo,
                'nombre' => $tasa->nombre,
                'simbolo' => $tasa->simbolo,
                'ultima_tasa' => $tasa->tasasHistorial->first()
            ];
        });

        return response()->json(['data' => $tasasVigentes]);
    }
}
