<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use App\Models\PagoProveedor;
use App\Models\Servicio;
use Illuminate\Http\Request;

class PagoProveedorController extends Controller
{
    public function index()
    {
        // Lista los pagos a proveedores y los devuelve en JSON.
        return response()->json(PagoProveedor::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Registra un pago y actualiza el estatus del servicio si se completa.
        $data = $request->validate([
            'id_servicio' => ['required', 'exists:servicios,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'referencia' => ['required', 'string', 'max:255'],
            'fecha_pago' => ['required', 'date'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id'],
        ]);

        $this->validarMontoServicio($data['id_servicio'], $data['monto']);

        $pago = PagoProveedor::create($data);

        $this->actualizarEstatusServicio($data['id_servicio']);

        return response()->json($pago, 201);
    }

    public function show(PagoProveedor $pagoProveedor)
    {
        // Muestra un pago a proveedor por id.
        return response()->json($pagoProveedor);
    }

    public function update(Request $request, PagoProveedor $pagoProveedor)
    {
        // Actualiza un pago y recalcula el estatus del servicio.
        $data = $request->validate([
            'id_servicio' => ['sometimes', 'required', 'exists:servicios,id'],
            'monto' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'referencia' => ['sometimes', 'required', 'string', 'max:255'],
            'fecha_pago' => ['sometimes', 'required', 'date'],
            'id_metodo_pago' => ['sometimes', 'required', 'exists:metodos_pago,id'],
        ]);

        $idServicio = $data['id_servicio'] ?? $pagoProveedor->id_servicio;
        $nuevoMonto = $data['monto'] ?? $pagoProveedor->monto;

        $this->validarMontoServicio($idServicio, $nuevoMonto, $pagoProveedor->id);

        $pagoProveedor->update($data);

        $this->actualizarEstatusServicio($idServicio);

        return response()->json($pagoProveedor);
    }

    public function destroy(PagoProveedor $pagoProveedor)
    {
        // Elimina el pago y recalcula el estatus del servicio.
        $idServicio = $pagoProveedor->id_servicio;

        $pagoProveedor->delete();

        $this->actualizarEstatusServicio($idServicio);

        return response()->json(['message' => 'Deleted']);
    }

    private function validarMontoServicio(int $idServicio, float $monto, ?int $ignoreId = null): void
    {
        $servicio = Servicio::find($idServicio);

        if (!$servicio) {
            abort(422, 'Servicio no encontrado');
        }

        $totalPagado = PagoProveedor::where('id_servicio', $idServicio)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->sum('monto');

        if (($totalPagado + $monto) > $servicio->total_servicio) {
            abort(422, 'El monto supera el total del servicio');
        }
    }

    private function actualizarEstatusServicio(int $idServicio): void
    {
        $servicio = Servicio::find($idServicio);

        if (!$servicio) {
            return;
        }

        $totalPagado = PagoProveedor::where('id_servicio', $idServicio)->sum('monto');

        if ($totalPagado >= $servicio->total_servicio) {
            $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);
            $servicio->update(['estatus' => $estatusPagado->id]);
        }
    }
}
