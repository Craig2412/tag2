<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConfiguracionSistemaResource;
use App\Services\ExchangeRate\ExchangeRateSyncService;
use Illuminate\Http\Request;

class ExchangeRateSyncController extends Controller
{
    /**
     * Obtener la configuración de sincronización de tasas
     *
     * Devuelve si la actualización automática está activa, cada cuántos minutos se ejecuta
     * y el resultado de la última ejecución.
     */
    public function show()
    {
        return new ConfiguracionSistemaResource(ExchangeRateSyncService::configuration());
    }

    /**
     * Actualizar la configuración de sincronización de tasas
     *
     * @bodyParam tasas_sync_enabled boolean required Activa la actualización automática. Ejemplo: true
     * @bodyParam tasas_sync_interval_minutes int required Intervalo en minutos entre ejecuciones. Ejemplo: 1440
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'tasas_sync_enabled' => ['required', 'boolean'],
            'tasas_sync_interval_minutes' => ['required', 'integer', 'min:15', 'max:10080'],
        ]);

        $configuration = ExchangeRateSyncService::configuration();
        $configuration->update($data);

        return new ConfiguracionSistemaResource($configuration->fresh());
    }

    /**
     * Sincronizar tasas de cambio manualmente
     *
     * Consulta DolarAPI y registra la cotización del día para USD BCV, EUR BCV y paralelo.
     */
    public function sync(ExchangeRateSyncService $syncService)
    {
        return response()->json(['data' => $syncService->syncNow()]);
    }
}
