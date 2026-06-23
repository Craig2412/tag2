<?php

namespace App\Http\Controllers;

use App\Http\Resources\LogroPersonalResource;
use App\Models\LogroPersonal;
use Illuminate\Http\Request;

class LogroPersonalController extends Controller
{
    /**
     * Listar logros del personal
     *
     * Devuelve el historial de logros del personal con filtros opcionales por usuario, tipo de entidad y paginación.
     *
     * @queryParam id_personal int Filtrar por ID del usuario con rol personal. Ejemplo: 1
     * @queryParam tipo_entidad string Filtrar por tipo de entidad (atencion, cotizacion, orden_compra). Ejemplo: atencion
     * @queryParam id_entidad int Filtrar por ID de la entidad. Ejemplo: 1
     * @queryParam per_page int Cantidad de resultados por página. Ejemplo: 50
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'id_personal' => ['sometimes', 'integer', 'exists:usuarios,id'],
            'tipo_entidad' => ['sometimes', 'in:atencion,cotizacion,orden_compra'],
            'id_entidad' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $query = LogroPersonal::query()
            ->with(['personal'])
            ->orderByDesc('id');

        if (isset($data['id_personal'])) {
            $query->where('id_personal', $data['id_personal']);
        }

        if (isset($data['tipo_entidad'])) {
            $query->where('tipo_entidad', $data['tipo_entidad']);
        }

        if (isset($data['id_entidad'])) {
            $query->where('id_entidad', $data['id_entidad']);
        }

        $perPage = $data['per_page'] ?? 50;

        return LogroPersonalResource::collection($query->paginate($perPage));
    }
}
