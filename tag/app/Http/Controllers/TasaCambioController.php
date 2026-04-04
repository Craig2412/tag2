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

    /**
     * Listar todas las tasas de cambio
     *
     * Devuelve el historial de tasas de cambio activas registradas en el sistema.
     */
    public function index()
    {
        // Lista las tasas activas y las devuelve en JSON.
        $tasas = TasaCambio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($tasas);
    }

    /**
     * Registrar tasa de cambio del día
     *
     * Crea la tasa de cambio del día actual aplicando el porcentaje de incremento personalizado sobre las tasas base.
     * Solo se permite un registro por día.
     *
     * @bodyParam tasa_usd number required Tasa base USD (antes del incremento). Ejemplo: 36.50
     * @bodyParam tasa_eur number required Tasa base EUR (antes del incremento). Ejemplo: 39.20
     * @bodyParam tasa_binance number required Tasa base Binance/P2P (antes del incremento). Ejemplo: 37.10
     * @bodyParam tasa_personalizada number required Porcentaje de incremento a aplicar sobre las tasas base. Ejemplo: 5.5
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente. Ejemplo: false
     */
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

    /**
     * Obtener una tasa de cambio específica
     *
     * Devuelve los datos de una tasa de cambio por su ID.
     */
    public function show(TasaCambio $tasaCambio)
    {
        // Muestra la tasa si no esta borrada.
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($tasaCambio);
    }

    /**
     * Actualizar una tasa de cambio
     *
     * Modifica una tasa de cambio activa. Si se envían los campos de tasa, se recalculan con el porcentaje personalizado.
     *
     * @bodyParam tasa_usd number Tasa base USD.
     * @bodyParam tasa_eur number Tasa base EUR.
     * @bodyParam tasa_binance number Tasa base Binance/P2P.
     * @bodyParam tasa_personalizada number Porcentaje de incremento a aplicar.
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente.
     */
    public function update(Request $request, TasaCambio $tasaCambio)
    {
        // Actualiza una tasa activa y devuelve el resultado.
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
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

    /**
     * Eliminar una tasa de cambio
     *
     * Realiza la eliminación lógica de la tasa de cambio.
     */
    public function destroy(TasaCambio $tasaCambio)
    {
        // Marca la tasa como borrada de forma logica.
        if ($tasaCambio->borrado_logico) {
            return response()->json(['message' => 'Ya estaba eliminada']);
        }

        $tasaCambio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Eliminado correctamente']);
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
