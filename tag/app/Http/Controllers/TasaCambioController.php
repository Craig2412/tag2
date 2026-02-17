<?php

namespace App\Http\Controllers;

use App\Models\TasaCambio;
use Illuminate\Http\Request;

class TasaCambioController extends Controller
{
    public function index()
    {
        $tasas = TasaCambio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($tasas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tasa_usd' => ['required', 'numeric'],
            'tasa_eur' => ['required', 'numeric'],
            'tasa_binance' => ['required', 'numeric'],
            'tasa_personalizada' => ['required', 'numeric'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $data['fecha'] = now()->toDateString();
        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $tasa = TasaCambio::create($data);

        return response()->json($tasa, 201);
    }

    public function show(TasaCambio $tasaCambio)
    {
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($tasaCambio);
    }

    public function update(Request $request, TasaCambio $tasaCambio)
    {
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
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $tasaCambio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }
}
