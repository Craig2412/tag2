<?php

namespace App\Http\Controllers;

use App\Http\Resources\EntidadBancariaResource;
use App\Models\EntidadBancaria;
use Illuminate\Http\Request;

class EntidadBancariaController extends Controller
{
    /**
     * Listar todas las entidades bancarias
     *
     * Devuelve el catálogo de entidades bancarias registradas en el sistema.
     */
    public function index(Request $request)
    {
        $query = EntidadBancaria::query();

        if ($request->has('include')) {
            $allowed = ['metodosPago', 'estatus_relation'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (! empty($includes)) {
                $query->with($includes);
            }
        }

        return EntidadBancariaResource::collection($query->orderBy('entidad')->get());
    }

    /**
     * Crear una nueva entidad bancaria
     *
     * @bodyParam entidad string required Nombre de la entidad bancaria. Ejemplo: Banco de Venezuela
     * @bodyParam estatus int ID del estatus. Ejemplo: 1
     * @bodyParam metodos_pago int[] Lista de IDs de métodos de pago asociados. Example: [1, 2]
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'entidad' => ['required', 'string', 'max:255'],
            'metodos_pago' => ['nullable', 'array'],
            'metodos_pago.*' => ['exists:metodos_pago,id'],
        ]);
        $entidad = EntidadBancaria::create($data);

        if (isset($data['metodos_pago'])) {
            $entidad->metodosPago()->sync($data['metodos_pago']);
        }

        return new EntidadBancariaResource($entidad->load('metodosPago'));
    }

    /**
     * Obtener una entidad bancaria específica
     */
    public function show(EntidadBancaria $entidadBancaria)
    {
        return new EntidadBancariaResource($entidadBancaria->load('metodosPago'));
    }

    /**
     * Actualizar una entidad bancaria existente
     *
     * @bodyParam entidad string Nombre de la entidad bancaria.
     * @bodyParam metodos_pago int[] Lista de IDs de métodos de pago asociados. Example: [1, 2]
     */
    public function update(Request $request, EntidadBancaria $entidadBancaria)
    {
        $data = $request->validate([
            'entidad' => ['sometimes', 'required', 'string', 'max:255'],
            'metodos_pago' => ['nullable', 'array'],
            'metodos_pago.*' => ['exists:metodos_pago,id'],
        ]);
        $entidadBancaria->update($data);

        if (isset($data['metodos_pago'])) {
            $entidadBancaria->metodosPago()->sync($data['metodos_pago']);
        }

        return new EntidadBancariaResource($entidadBancaria->load('metodosPago'));
    }

    /**
     * Eliminar una entidad bancaria
     */
    public function destroy(EntidadBancaria $entidadBancaria)
    {
        $entidadBancaria->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
}
