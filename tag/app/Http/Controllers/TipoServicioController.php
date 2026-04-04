<?php

namespace App\Http\Controllers;

use App\Models\TipoServicio;
use Illuminate\Http\Request;

class TipoServicioController extends Controller
{
    /**
     * Listar todos los tipos de servicio
     *
     * Devuelve el catálogo de tipos de servicio activos (no eliminados).
     */
    public function index()
    {
        // Lista los tipos de servicio activos y los devuelve en JSON.
        $tipos = TipoServicio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($tipos);
    }

    /**
     * Crear un nuevo tipo de servicio
     * 
     * @bodyParam tipo_servicio string required Nombre descriptivo del tipo de servicio. Ejemplo: Emisión de Boletos
     * @bodyParam id_proveedor int required ID del proveedor principal para este tipo de servicio. Ejemplo: 1
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente. Ejemplo: false
     */
    public function store(Request $request)
    {
        // Crea un tipo de servicio con datos validados y lo devuelve.
        $data = $request->validate([
            'tipo_servicio' => ['required', 'string', 'max:255'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $tipoServicio = TipoServicio::create($data);

        return response()->json($tipoServicio, 201);
    }

    /**
     * Obtener un tipo de servicio específico
     *
     * Devuelve los datos de un tipo de servicio por su ID.
     */
    public function show(TipoServicio $tipoServicio)
    {
        // Muestra un tipo de servicio si no está marcado como borrado.
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($tipoServicio);
    }

    /**
     * Actualizar un tipo de servicio existente
     * 
     * @bodyParam tipo_servicio string Nombre del tipo de servicio.
     * @bodyParam id_proveedor int ID del proveedor asociado.
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente.
     */
    public function update(Request $request, TipoServicio $tipoServicio)
    {
        // Actualiza un tipo de servicio activo y devuelve el resultado.
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $data = $request->validate([
            'tipo_servicio' => ['sometimes', 'required', 'string', 'max:255'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $tipoServicio->update($data);

        return response()->json($tipoServicio);
    }

    /**
     * Eliminar un tipo de servicio
     */
    public function destroy(TipoServicio $tipoServicio)
    {
        // Marca el tipo de servicio como borrado lógico.
        if ($tipoServicio->borrado_logico) {
            return response()->json(['message' => 'Ya estaba eliminado']);
        }

        $tipoServicio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
