<?php

namespace App\Http\Controllers;

use App\Models\EntidadBancaria;
use App\Http\Resources\EntidadBancariaResource;
use App\Http\Resources\MetodoPagoResource;
use Illuminate\Http\Request;

class EntidadBancariaController extends Controller
{
    /**
     * Listar todas las entidades bancarias
     *
     * Devuelve el catálogo de entidades bancarias registradas en el sistema.
     */
    public function index()
    {
        return EntidadBancariaResource::collection(EntidadBancaria::with('metodosPago')->get());
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
            'estatus' => ['nullable', 'exists:estatus,id'],
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
     * @bodyParam estatus int ID del estatus.
     * @bodyParam metodos_pago int[] Lista de IDs de métodos de pago asociados. Example: [1, 2]
     */
    public function update(Request $request, EntidadBancaria $entidadBancaria)
    {
        $data = $request->validate([
            'entidad' => ['sometimes', 'required', 'string', 'max:255'],
            'estatus' => ['nullable', 'exists:estatus,id'],
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
