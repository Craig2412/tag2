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
        // Crea una tasa de cambio, asigna la fecha de hoy y la devuelve.
        $data = $request->validate([
            'tasa_usd' => ['required', 'numeric'],
            'tasa_eur' => ['required', 'numeric'],
            'tasa_binance' => ['required', 'numeric'],
            'tasa_personalizada' => ['required', 'numeric'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

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
            'tasa_usd' => ['sometimes', 'required', 'numeric'],
            'tasa_eur' => ['sometimes', 'required', 'numeric'],
            'tasa_binance' => ['sometimes', 'required', 'numeric'],
            'tasa_personalizada' => ['sometimes', 'required', 'numeric'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

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
}
