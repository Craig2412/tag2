<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index()
    {
        // Lista los servicios activos y los devuelve en JSON.
        $servicios = Servicio::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($servicios);
    }

    public function store(Request $request)
    {
        // Crea un servicio con datos validados y lo devuelve.
        $data = $request->validate([
            'id_tipo_servicio' => ['required', 'exists:tipo_servicio,id'],
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'costo' => ['required', 'numeric'],
            'monto_gravable' => ['required', 'numeric'],
            'monto_no_sujeto' => ['required', 'numeric'],
            'iva_establecido' => ['nullable', 'numeric'],
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

    public function show(Servicio $servicio)
    {
        // Muestra un servicio si no esta marcado como borrado.
        if ($servicio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($servicio);
    }

    public function update(Request $request, Servicio $servicio)
    {
        // Actualiza un servicio activo y devuelve el resultado.
        if ($servicio->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'id_tipo_servicio' => ['sometimes', 'required', 'exists:tipo_servicio,id'],
            'id_proveedor' => ['sometimes', 'required', 'exists:proveedores,id'],
            'costo' => ['sometimes', 'required', 'numeric'],
            'monto_gravable' => ['sometimes', 'required', 'numeric'],
            'monto_no_sujeto' => ['sometimes', 'required', 'numeric'],
            'iva_establecido' => ['nullable', 'numeric'],
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

    public function destroy(Servicio $servicio)
    {
        // Marca el servicio como borrado logico.
        if ($servicio->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $servicio->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }

    private function calcularTotalServicio(float $montoGravable, float $montoNoSujeto): float
    {
        return $montoGravable + $montoNoSujeto;
    }
}
