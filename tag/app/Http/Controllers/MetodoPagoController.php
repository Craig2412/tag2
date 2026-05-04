<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
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
        // Lista los metodos de pago y los devuelve en JSON.
        return MetodoPagoResource::collection(MetodoPago::orderBy('id')->get());
    }

    /**
     * Crear un nuevo método de pago
     * 
     * @bodyParam metodo_pago string required Nombre del método de pago. Ejemplo: Efectivo - Divisas
     */
    public function store(Request $request)
    {
        // Crea un metodo de pago con datos validados y lo devuelve.
        $data = $request->validate([
            'metodo_pago' => ['required', 'string', 'max:255', 'unique:metodos_pago,metodo_pago'],
        ]);

        $item = MetodoPago::create($data);

        return new MetodoPagoResource($item);
    }

    /**
     * Obtener un método de pago específico
     *
     * Devuelve los datos de un método de pago por su ID.
     */
    public function show(MetodoPago $metodoPago)
    {
        // Muestra un metodo de pago por id.
        return new MetodoPagoResource($metodoPago);
    }

    /**
     * Actualizar un método de pago
     * 
     * @bodyParam metodo_pago string required Nombre del método de pago. Ejemplo: Transferencia Zelle
     */
    public function update(Request $request, MetodoPago $metodoPago)
    {
        // Actualiza un metodo de pago y devuelve el resultado.
        $data = $request->validate([
            'metodo_pago' => [
                'required',
                'string',
                'max:255',
                Rule::unique('metodos_pago', 'metodo_pago')->ignore($metodoPago->id),
            ],
        ]);

        $metodoPago->update($data);

        return new MetodoPagoResource($metodoPago);
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
}
