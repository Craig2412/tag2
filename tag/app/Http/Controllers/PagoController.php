<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function index()
    {
        // Lista los pagos activos y los devuelve en JSON.
        $items = Pago::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        // Registra un pago y distribuye montos entre ordenes de compra.
        $data = $request->validate([
            'fecha_pago' => ['required', 'date'],
            'monto_total' => ['required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['required', 'string', 'max:255'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
            'id_entidad_bancaria' => ['required', 'exists:entidades_bancarias,id'],
            'estatus' => ['required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
            'ordenes_compra' => ['required', 'array', 'min:1'],
            'ordenes_compra.*.id_orden_compra' => ['required', 'exists:ordenes_compra,id'],
            'ordenes_compra.*.monto_asignado' => ['required', 'numeric', 'min:0.01'],
        ]);

        $sumaAsignada = collect($data['ordenes_compra'])->sum('monto_asignado');
        if (abs($sumaAsignada - $data['monto_total']) > 0.01) {
            return response()->json(['message' => 'La suma asignada debe coincidir con el monto total'], 422);
        }

        foreach ($data['ordenes_compra'] as $detalle) {
            $totalServicios = $this->totalServiciosOrdenCompra($detalle['id_orden_compra']);
            if ($totalServicios <= 0) {
                return response()->json(['message' => 'La orden de compra no tiene servicios asociados'], 422);
            }

            $totalPagado = $this->totalPagadoOrdenCompra($detalle['id_orden_compra']);
            if (($totalPagado + $detalle['monto_asignado']) > $totalServicios) {
                return response()->json(['message' => 'El monto supera el total de la orden de compra'], 422);
            }
        }

        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $pago = DB::transaction(function () use ($data) {
            $ordenesCompra = $data['ordenes_compra'];
            unset($data['ordenes_compra']);

            $pago = Pago::create($data);

            foreach ($ordenesCompra as $detalle) {
                PagoOrdenCompra::create([
                    'id_pago' => $pago->id,
                    'id_orden_compra' => $detalle['id_orden_compra'],
                    'monto_asignado' => $detalle['monto_asignado'],
                ]);
            }

            return $pago;
        });

        foreach ($data['ordenes_compra'] as $detalle) {
            $this->actualizarEstatusOrdenCompra($detalle['id_orden_compra']);
        }

        return response()->json($pago, 201);
    }

    public function show(Pago $pago)
    {
        // Muestra un pago si no esta marcado como borrado.
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $pago->load('ordenesCompra');

        return response()->json($pago);
    }

    public function update(Request $request, Pago $pago)
    {
        // Actualiza un pago y recalcula montos por orden de compra.
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'fecha_pago' => ['sometimes', 'required', 'date'],
            'monto_total' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['sometimes', 'required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['sometimes', 'required', 'string', 'max:255'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'id_entidad_bancaria' => ['sometimes', 'required', 'exists:entidades_bancarias,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
            'ordenes_compra' => ['sometimes', 'required', 'array', 'min:1'],
            'ordenes_compra.*.id_orden_compra' => ['required', 'exists:ordenes_compra,id'],
            'ordenes_compra.*.monto_asignado' => ['required', 'numeric', 'min:0.01'],
        ]);

        $ordenesCompra = $data['ordenes_compra'] ?? null;
        if ($ordenesCompra) {
            $montoTotal = $data['monto_total'] ?? $pago->monto_total;
            $sumaAsignada = collect($ordenesCompra)->sum('monto_asignado');

            if (abs($sumaAsignada - $montoTotal) > 0.01) {
                return response()->json(['message' => 'La suma asignada debe coincidir con el monto total'], 422);
            }

            foreach ($ordenesCompra as $detalle) {
                $totalServicios = $this->totalServiciosOrdenCompra($detalle['id_orden_compra']);
                if ($totalServicios <= 0) {
                    return response()->json(['message' => 'La orden de compra no tiene servicios asociados'], 422);
                }

                $totalPagado = $this->totalPagadoOrdenCompra($detalle['id_orden_compra'], $pago->id);
                if (($totalPagado + $detalle['monto_asignado']) > $totalServicios) {
                    return response()->json(['message' => 'El monto supera el total de la orden de compra'], 422);
                }
            }
        }

        DB::transaction(function () use ($data, $pago, $ordenesCompra) {
            $updateData = $data;
            unset($updateData['ordenes_compra']);

            if (!empty($updateData)) {
                $pago->update($updateData);
            }

            if ($ordenesCompra) {
                PagoOrdenCompra::where('id_pago', $pago->id)->delete();

                foreach ($ordenesCompra as $detalle) {
                    PagoOrdenCompra::create([
                        'id_pago' => $pago->id,
                        'id_orden_compra' => $detalle['id_orden_compra'],
                        'monto_asignado' => $detalle['monto_asignado'],
                    ]);
                }
            }
        });

        $ordenesActuales = $ordenesCompra
            ? array_column($ordenesCompra, 'id_orden_compra')
            : PagoOrdenCompra::where('id_pago', $pago->id)
                ->pluck('id_orden_compra')
                ->all();

        foreach ($ordenesActuales as $idOrdenCompra) {
            $this->actualizarEstatusOrdenCompra($idOrdenCompra);
        }

        return response()->json($pago->fresh('ordenesCompra'));
    }

    public function destroy(Pago $pago)
    {
        // Marca el pago como borrado y recalcula el estatus de la orden de compra.
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $pago->update(['borrado_logico' => true]);

        $ordenesCompra = PagoOrdenCompra::where('id_pago', $pago->id)
            ->pluck('id_orden_compra')
            ->all();

        foreach ($ordenesCompra as $idOrdenCompra) {
            $this->actualizarEstatusOrdenCompra($idOrdenCompra);
        }

        return response()->json(['message' => 'Deleted']);
    }

    private function totalServiciosOrdenCompra(int $idOrdenCompra): float
    {
        return (float) (OrdenCompra::where('id', $idOrdenCompra)->value('monto_total') ?? 0);
    }

    private function actualizarEstatusOrdenCompra(int $idOrdenCompra): void
    {
        $totalServicios = $this->totalServiciosOrdenCompra($idOrdenCompra);
        $totalPagado = $this->totalPagadoOrdenCompra($idOrdenCompra);

        if ($totalServicios <= 0) {
            return;
        }

        $estatusPendiente = Estatus::firstOrCreate(['estatus' => 'pendiente de pago']);

        if ($totalPagado >= $totalServicios) {
            $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);
            OrdenCompra::where('id', $idOrdenCompra)->update(['estatus' => $estatusPagado->id]);
            return;
        }

        OrdenCompra::where('id', $idOrdenCompra)->update(['estatus' => $estatusPendiente->id]);
    }

    private function totalPagadoOrdenCompra(int $idOrdenCompra, ?int $excludePagoId = null): float
    {
        $pagos = DB::table('pagos_ordenes_compra as poc')
            ->join('pagos as p', 'poc.id_pago', '=', 'p.id')
            ->where('poc.id_orden_compra', $idOrdenCompra)
            ->where('p.borrado_logico', false);

        if ($excludePagoId) {
            $pagos->where('p.id', '!=', $excludePagoId);
        }

        return (float) $pagos->sum('poc.monto_asignado');
    }
}
