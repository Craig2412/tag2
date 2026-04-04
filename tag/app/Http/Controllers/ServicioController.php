<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    /**
     * Listar todos los servicios
     *
     * Devuelve el listado de servicios activos (no eliminados) registrados en el sistema.
     */
    public function index()
    {
        // Lista los servicios activos y los devuelve en JSON.
        $servicios = Servicio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($servicios);
    }

    /**
     * Crear un nuevo servicio
     * 
     * @bodyParam id_tipo_servicio int required ID del tipo de servicio. Ejemplo: 1
     * @bodyParam id_proveedor int required ID del proveedor del servicio. Ejemplo: 1
     * @bodyParam costo number required Precio de costo del servicio. Ejemplo: 150.00
     * @bodyParam monto_gravable number required Monto sobre el cual se aplica IVA. Ejemplo: 150.00
     * @bodyParam monto_no_sujeto number required Monto libre de impuestos. Ejemplo: 0.00
     * @bodyParam id_tasa_cambio int required ID de la tasa de cambio aplicada. Ejemplo: 1
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente. Ejemplo: false
     */
    public function store(Request $request)
    {
        // Crea un servicio con datos validados y lo devuelve.
        $data = $request->validate([
            'id_tipo_servicio' => ['required', 'exists:tipo_servicio,id'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'costo' => ['required', 'numeric'],
            'monto_gravable' => ['required', 'numeric'],
            'monto_no_sujeto' => ['required', 'numeric'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $data['total_servicio'] = $this->calcularTotalServicio(
            $data['monto_gravable'],
            $data['monto_no_sujeto']
        );
        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $servicio = Servicio::create($data);

        return response()->json($servicio, 201);
    }

    /**
     * Obtener un servicio específico
     *
     * Devuelve los datos de un servicio por su ID.
     */
    public function show(Servicio $servicio)
    {
        // Muestra un servicio si no está marcado como borrado.
        if ($servicio->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($servicio);
    }

    /**
     * Actualizar un servicio existente
     * 
     * @bodyParam id_tipo_servicio int ID del tipo de servicio.
     * @bodyParam id_proveedor int ID del proveedor.
     * @bodyParam costo number Precio de costo.
     * @bodyParam monto_gravable number Monto gravable.
     * @bodyParam monto_no_sujeto number Monto no sujeto a impuestos.
     * @bodyParam id_tasa_cambio int ID de la tasa de cambio.
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente.
     */
    public function update(Request $request, Servicio $servicio)
    {
        // Actualiza un servicio activo y devuelve el resultado.
        if ($servicio->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $data = $request->validate([
            'id_tipo_servicio' => ['sometimes', 'required', 'exists:tipo_servicio,id'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'costo' => ['sometimes', 'required', 'numeric'],
            'monto_gravable' => ['sometimes', 'required', 'numeric'],
            'monto_no_sujeto' => ['sometimes', 'required', 'numeric'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('monto_gravable', $data) || array_key_exists('monto_no_sujeto', $data)) {
            $gravable = $data['monto_gravable'] ?? $servicio->monto_gravable;
            $noSujeto = $data['monto_no_sujeto'] ?? $servicio->monto_no_sujeto;
            $data['total_servicio'] = $this->calcularTotalServicio($gravable, $noSujeto);
        }

        $servicio->update($data);

        return response()->json($servicio);
    }

    /**
     * Eliminar un servicio
     */
    public function destroy(Servicio $servicio)
    {
        // Marca el servicio como borrado lógico.
        if ($servicio->borrado_logico) {
            return response()->json(['message' => 'Ya estaba eliminado']);
        }

        $servicio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Eliminado correctamente']);
    }

    private function calcularTotalServicio(float $montoGravable, float $montoNoSujeto): float
    {
        return $montoGravable + $montoNoSujeto;
    }
}
