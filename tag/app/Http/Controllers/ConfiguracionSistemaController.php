<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;

class ConfiguracionSistemaController extends Controller
{
    public function index()
    {
        // Lista las configuraciones del sistema y las devuelve en JSON.
        return response()->json(ConfiguracionSistema::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Crea una configuracion del sistema con datos validados y la devuelve.
        $data = $request->validate([
            'dias_vencimiento' => ['required', 'integer', 'min:0'],
        ]);

        $item = ConfiguracionSistema::create($data);

        return response()->json($item, 201);
    }

    public function show(ConfiguracionSistema $configuracionSistema)
    {
        // Muestra una configuracion del sistema por id.
        return response()->json($configuracionSistema);
    }

    public function update(Request $request, ConfiguracionSistema $configuracionSistema)
    {
        // Actualiza una configuracion del sistema y devuelve el resultado.
        $data = $request->validate([
            'dias_vencimiento' => ['required', 'integer', 'min:0'],
        ]);

        $configuracionSistema->update($data);

        return response()->json($configuracionSistema);
    }

    public function destroy(ConfiguracionSistema $configuracionSistema)
    {
        // Elimina una configuracion del sistema y confirma el resultado.
        $configuracionSistema->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
