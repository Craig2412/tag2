<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Http\Resources\PagoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    /**
     * Listar todos los pagos de clientes
     *
     * Devuelve todos los pagos activos registrados en el sistema.
     */
    public function index()
    {
        $items = Pago::orderBy('id')->get();
        return PagoResource::collection($items);
    }

    /**
     * Registrar un nuevo pago de cliente
     *
     * Permite registrar un abono de un cliente y distribuirlo entre varias órdenes de compra.
     *
     * @bodyParam fecha_pago date required Fecha del pago. Ejemplo: 2026-04-11
     * @bodyParam monto_total number required Monto total del pago. Ejemplo: 500.00
     * @bodyParam id_metodo_pago int required ID del método de pago. Ejemplo: 1
     * @bodyParam nro_comprobante string required Número de referencia o comprobante. Ejemplo: REF-998877
     * @bodyParam id_tasa_cambio int required ID de la tasa de cambio aplicada. Ejemplo: 1
     * @bodyParam id_entidad_bancaria int required ID de la entidad bancaria. Ejemplo: 1
     * @bodyParam estatus int required ID del estatus del pago. Ejemplo: 1
     * @bodyParam ordenes_compra object[] required Distribución en órdenes de compra.
     * @bodyParam ordenes_compra[].id_orden_compra int required ID de la orden de compra. Ejemplo: 1
     * @bodyParam ordenes_compra[].monto_asignado number required Monto asignado a esta orden. Ejemplo: 500.00
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha_pago' => ['required', 'date'],
            'monto_total' => ['required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['required', 'string', 'max:255'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
            'id_entidad_bancaria' => ['required', 'exists:entidades_bancarias,id'],
            'estatus' => ['required', 'exists:estatus,id'],
            'ordenes_compra' => ['required', 'array', 'min:1'],
            'ordenes_compra.*.id_orden_compra' => ['required', 'exists:ordenes_compra,id'],
            'ordenes_compra.*.monto_asignado' => ['required', 'numeric', 'min:0.01'],
            'comprobante_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:4096'], // 4MB max
        ]);

        // Manejo del archivo PDF (opcional)
        $rutaComprobante = null;
        if ($request->hasFile('comprobante_pdf')) {
            $file = $request->file('comprobante_pdf');
            // Validación extra de seguridad MIME
            if ($file->getMimeType() !== 'application/pdf') {
                return response()->json(['message' => 'El archivo debe ser un PDF válido'], 422);
            }
            $nombreSeguro = \Str::random(40) . '.pdf';
            $rutaComprobante = $file->storeAs('public/comprobantes_pagos', $nombreSeguro);
            // Guardar solo la ruta relativa para exponerla luego
            $rutaComprobante = str_replace('public/', 'storage/', $rutaComprobante);
        }

        $sumaAsignada = collect($data['ordenes_compra'])->sum('monto_asignado');
        if (abs($sumaAsignada - $data['monto_total']) > 0.01) {
            return response()->json(['message' => 'La suma asignada debe coincidir con el monto total'], 422);
        }

        foreach ($data['ordenes_compra'] as $detalle) {
            $totalServicios = $this->totalServiciosOrdenCompra($detalle['id_orden_compra']);
            if ($totalServicios <= 0) {
                return response()->json(['message' => 'La orden de compra no tiene servicios asociados'], 422);
            }
        }

        $pago = DB::transaction(function () use ($data) {
            $ordenesCompra = $data['ordenes_compra'];
            unset($data['ordenes_compra']);
            if ($rutaComprobante) {
                $data['comprobante_pdf'] = $rutaComprobante;
            }
            $pago = Pago::create($data);
            foreach ($ordenesCompra as $detalle) {
                // Al crear este pivote, el PagoOrdenCompraObserver recalculará el estado_financiero automáticamente.
                PagoOrdenCompra::create([
                    'id_pago' => $pago->id,
                    'id_orden_compra' => $detalle['id_orden_compra'],
                    'monto_asignado' => $detalle['monto_asignado'],
                ]);
            }
            return $pago;
        });

        return new PagoResource($pago);
    }

    /**
     * Obtener un pago específico
     */
    public function show(Pago $pago)
    {
        return new PagoResource($pago->load('ordenesCompra'));
    }

    /**
     * Actualizar un pago existente
     *
     * @bodyParam fecha_pago date Fecha del pago. Ejemplo: 2026-04-11
     * @bodyParam monto_total number Monto total. Ejemplo: 500.00
     * @bodyParam id_metodo_pago int ID del método de pago. Ejemplo: 1
     * @bodyParam nro_comprobante string Número de comprobante. Ejemplo: REF-998877
     * @bodyParam id_tasa_cambio int ID de la tasa de cambio. Ejemplo: 1
     * @bodyParam id_entidad_bancaria int ID de la entidad bancaria. Ejemplo: 1
     * @bodyParam estatus int ID del estatus. Ejemplo: 1
     * @bodyParam ordenes_compra object[] Distribución en órdenes de compra.
     * @bodyParam ordenes_compra[].id_orden_compra int required ID de la orden de compra. Ejemplo: 1
     * @bodyParam ordenes_compra[].monto_asignado number required Monto asignado. Ejemplo: 500.00
     */
    public function update(Request $request, Pago $pago)
    {
        $data = $request->validate([
            'fecha_pago' => ['sometimes', 'required', 'date'],
            'monto_total' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['sometimes', 'required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['sometimes', 'required', 'string', 'max:255'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'id_entidad_bancaria' => ['sometimes', 'required', 'exists:entidades_bancarias,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
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
                    return response()->json(['message' => 'La orden de compra no tiene servicios aplicados'], 422);
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
                // Al eliminar los pivotes viejos, el Observer recalcula la orden en tiempo real
                PagoOrdenCompra::where('id_pago', $pago->id)->delete(); 

                foreach ($ordenesCompra as $detalle) {
                    // Al crear los nuevos, vuelve a recalcular y estabiliza el estado_financiero
                    PagoOrdenCompra::create([
                        'id_pago' => $pago->id,
                        'id_orden_compra' => $detalle['id_orden_compra'],
                        'monto_asignado' => $detalle['monto_asignado'],
                    ]);
                }
            }
        });

        return new PagoResource($pago->fresh()->load('ordenesCompra'));
    }

    /**
     * Eliminar un pago
     *
     * El orden es crítico:
     * 1. Eliminar los pivotes PRIMERO → el PagoOrdenCompraObserver recalcula el
     *    estado_financiero de cada OrdenCompra con el pago todavía visible (softDeletes).
     * 2. Soft-delete del pago DESPUÉS → ya no afecta los recálculos.
     * Todo en una transacción para garantizar atomicidad.
     */
    public function destroy(Pago $pago)
    {
        DB::transaction(function () use ($pago) {
            // 1. Eliminar pivotes → dispara PagoOrdenCompraObserver::deleted()
            //    En este momento el pago padre aún NO está soft-deleted,
            //    por lo que el Observer puede leer correctamente los montos vigentes.
            PagoOrdenCompra::where('id_pago', $pago->id)->each(fn ($pivote) => $pivote->delete());

            // 2. Soft-delete del pago padre
            $pago->delete();
        });

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }

    private function totalServiciosOrdenCompra(int $idOrdenCompra): float
    {
        return (float) (OrdenCompra::where('id', $idOrdenCompra)->value('monto_total') ?? 0);
    }

    private function totalPagadoOrdenCompra(int $idOrdenCompra, ?int $excludePagoId = null): float
    {
        $pagos = DB::table('pagos_ordenes_compra as poc')
            ->join('pagos as p', 'poc.id_pago', '=', 'p.id')
            ->where('poc.id_orden_compra', $idOrdenCompra)
            ->whereNull('p.deleted_at'); // Ahora usa softDeletes puro

        if ($excludePagoId) {
            $pagos->where('p.id', '!=', $excludePagoId);
        }

        return (float) $pagos->sum('poc.monto_asignado');
    }
}
