<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Http\Resources\EntidadBancariaResource;
use App\Http\Resources\MetodoPagoResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MetodoPagoController extends Controller
{
    /**
     * Listar todos los métodos de pago
     *
     * Devuelve el catálogo de métodos de pago disponibles en el sistema.
     */
    public function index()
    {
        // Lista los metodos de pago incluyendo sus entidades bancarias asociadas.
        $metodos = MetodoPago::with('entidadesBancarias')->orderBy('id')->get();
        return MetodoPagoResource::collection($metodos);
    }

    /**
     * Crear un nuevo método de pago
     * 
     * @bodyParam metodo_pago string required Nombre del método de pago. Ejemplo: Efectivo - Divisas
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'metodo_pago' => ['required', 'string', 'max:255', 'unique:metodos_pago,metodo_pago'],
            'entidades_bancarias' => ['nullable', 'array'],
            'entidades_bancarias.*' => ['exists:entidades_bancarias,id'],
        ]);

        $item = MetodoPago::create($data);

        if (isset($data['entidades_bancarias'])) {
            $item->entidadesBancarias()->sync($data['entidades_bancarias']);
        }

        return new MetodoPagoResource($item->load('entidadesBancarias'));
    }

    /**
     * Obtener un método de pago específico
     *
     * Devuelve los datos de un método de pago por su ID.
     */
    public function show(MetodoPago $metodoPago)
    {
        return new MetodoPagoResource($metodoPago->load('entidadesBancarias'));
    }

    /**
     * Actualizar un método de pago
     * 
     * @bodyParam metodo_pago string required Nombre del método de pago. Ejemplo: Transferencia Zelle
     * @bodyParam entidades_bancarias int[] Lista de IDs de entidades bancarias asociadas. Example: [1, 2]
     */
    public function update(Request $request, MetodoPago $metodoPago)
    {
        $data = $request->validate([
            'metodo_pago' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('metodos_pago', 'metodo_pago')->ignore($metodoPago->id),
            ],
            'entidades_bancarias' => ['nullable', 'array'],
            'entidades_bancarias.*' => ['exists:entidades_bancarias,id'],
        ]);

        $metodoPago->update($data);

        if (isset($data['entidades_bancarias'])) {
            $metodoPago->entidadesBancarias()->sync($data['entidades_bancarias']);
        }

        return new MetodoPagoResource($metodoPago->load('entidadesBancarias'));
    }

    /**
     * Eliminar un método de pago
     */
    public function destroy(MetodoPago $metodoPago)
    {
        // Elimina el metodo de pago y confirma el resultado.
        $metodoPago->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }
    /**
     * Obtener entidades bancarias de un método de pago
     *
     * Devuelve las entidades bancarias asociadas al método de pago dado.
     * Si el arreglo está vacío, el método no requiere entidad bancaria (ej. Efectivo).
     */
    public function entidadesBancarias(MetodoPago $metodoPago)
    {
        return EntidadBancariaResource::collection($metodoPago->entidadesBancarias);
    }
}
