<?php

namespace App\Http\Controllers;

use App\Models\TasaCambio;
use Illuminate\Http\Request;

class TasaCambioController extends Controller
{
    private function validarUnRegistroPorDia(string $fecha): ?\Illuminate\Http\JsonResponse
    {
        $existeRegistroDelDia = TasaCambio::whereDate('fecha', $fecha)->exists();

        if ($existeRegistroDelDia) {
            return response()->json([
                'message' => 'Ya existe una tasa de cambio registrada para la fecha de hoy',
            ], 422);
        }

        return null;
    }

    public function index()
    {
        // Lista las tasas activas y las devuelve en JSON.
        $tasas = TasaCambio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($tasas);
    }

    public function store(Request $request)
    {
        // Crea una tasa de cambio aplicando porcentaje personalizado a las tasas base.
        $data = $request->validate([
            'tasa_usd' => ['required', 'numeric', 'min:0.0001'],
            'tasa_eur' => ['required', 'numeric', 'min:0.0001'],
            'tasa_binance' => ['required', 'numeric', 'min:0.0001'],
            'tasa_personalizada' => ['required', 'numeric', 'min:0'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $data = $this->aplicarIncrementoPorcentual($data);

        $data['fecha'] = now()->toDateString();
        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $errorValidacion = $this->validarUnRegistroPorDia($data['fecha']);
        if ($errorValidacion) {
            return $errorValidacion;
        }

        $tasa = TasaCambio::create($data);

        return response()->json($tasa, 201);
    }

    public function show(TasaCambio $tasaCambio)
    {
        // Muestra la tasa si no esta borrada.
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($tasaCambio);
    }

    public function update(Request $request, TasaCambio $tasaCambio)
    {
        // Actualiza una tasa activa y devuelve el resultado.
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'tasa_usd' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'tasa_eur' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'tasa_binance' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'tasa_personalizada' => ['sometimes', 'required', 'numeric', 'min:0'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $camposTasa = ['tasa_usd', 'tasa_eur', 'tasa_binance', 'tasa_personalizada'];
        $algunCampoTasa = collect($camposTasa)->contains(fn (string $campo) => array_key_exists($campo, $data));

        if ($algunCampoTasa) {
            $faltantes = collect($camposTasa)
                ->filter(fn (string $campo) => !array_key_exists($campo, $data))
                ->values()
                ->all();

            if (!empty($faltantes)) {
                return response()->json([
                    'message' => 'Para recalcular tasas debes enviar tasa_usd, tasa_eur, tasa_binance y tasa_personalizada',
                    'faltantes' => $faltantes,
                ], 422);
            }

            $data = $this->aplicarIncrementoPorcentual($data);
        }

        $tasaCambio->update($data);

        return response()->json($tasaCambio);
    }

    public function destroy(TasaCambio $tasaCambio)
    {
        // Marca la tasa como borrada de forma logica.
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $tasaCambio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }

    private function aplicarIncrementoPorcentual(array $data): array
    {
        $porcentaje = (float) $data['tasa_personalizada'];
        $factor = 1 + ($porcentaje / 100);

        $data['tasa_usd'] = round(((float) $data['tasa_usd']) * $factor, 4);
        $data['tasa_eur'] = round(((float) $data['tasa_eur']) * $factor, 4);
        $data['tasa_binance'] = round(((float) $data['tasa_binance']) * $factor, 4);

        return $data;
    }
}
