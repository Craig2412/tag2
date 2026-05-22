<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConfiguracionSistemaResource;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;

class ConfiguracionSistemaController extends Controller
{
    /**
     * Listar configuraciones del sistema
     *
     * Devuelve todas las configuraciones del sistema registradas.
     */
    public function index()
    {
        // Lista las configuraciones del sistema y las devuelve en JSON.
        return ConfiguracionSistemaResource::collection(ConfiguracionSistema::orderBy('id')->get());
    }

    /**
     * Crear una configuración del sistema
     *
     * Registra una nueva configuración de parámetros globales del sistema.
     *
     * @bodyParam dias_vencimiento int required Días para que venza un proceso. Ejemplo: 30
     */
    public function store(Request $request)
    {
        // Crea una configuracion del sistema con datos validados y la devuelve.
        $data = $request->validate([
            'dias_vencimiento' => ['required', 'integer', 'min:0'],
        ]);

        $item = ConfiguracionSistema::create($data);

        return new ConfiguracionSistemaResource($item);
    }

    /**
     * Obtener una configuración del sistema específica
     *
     * Devuelve los datos de una configuración por su ID.
     */
    public function show(ConfiguracionSistema $configuracionSistema)
    {
        // Muestra una configuracion del sistema por id.
        return new ConfiguracionSistemaResource($configuracionSistema);
    }

    /**
     * Actualizar una configuración del sistema
     *
     * Modifica los parámetros de una configuración existente.
     *
     * @bodyParam dias_vencimiento int required Días para que venza un proceso. Ejemplo: 30
     */
    public function update(Request $request, ConfiguracionSistema $configuracionSistema)
    {
        // Actualiza una configuracion del sistema y devuelve el resultado.
        $data = $request->validate([
            'dias_vencimiento' => ['required', 'integer', 'min:0'],
        ]);

        $configuracionSistema->update($data);

        return new ConfiguracionSistemaResource($configuracionSistema);
    }

    /**
     * Eliminar una configuración del sistema
     *
     * Elimina permanentemente la configuración del sistema.
     */
    public function destroy(ConfiguracionSistema $configuracionSistema)
    {
        // Elimina una configuracion del sistema y confirma el resultado.
        $configuracionSistema->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
