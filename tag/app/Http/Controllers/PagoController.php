<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\Pago;
use App\Models\PagoCotizacion;
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
        // Registra un pago y distribuye montos entre cotizaciones.
        $data = $request->validate([
            'fecha_pago' => ['required', 'date'],
            'monto_total' => ['required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['required', 'string', 'max:255'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
            'estatus' => ['required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
            'cotizaciones' => ['required', 'array', 'min:1'],
            'cotizaciones.*.id_cotizacion' => ['required', 'exists:cotizaciones,id'],
            'cotizaciones.*.monto_asignado' => ['required', 'numeric', 'min:0.01'],
        ]);

        $sumaAsignada = collect($data['cotizaciones'])->sum('monto_asignado');
        if (abs($sumaAsignada - $data['monto_total']) > 0.01) {
            return response()->json(['message' => 'La suma asignada debe coincidir con el monto total'], 422);
        }

        foreach ($data['cotizaciones'] as $detalle) {
            $totalServicios = $this->totalServiciosCotizacion($detalle['id_cotizacion']);
            if ($totalServicios <= 0) {
                return response()->json(['message' => 'La cotizacion no tiene servicios asociados'], 422);
            }

            $totalPagado = $this->totalPagadoCotizacion($detalle['id_cotizacion']);
            if (($totalPagado + $detalle['monto_asignado']) > $totalServicios) {
                return response()->json(['message' => 'El monto supera el total de la cotizacion'], 422);
            }
        }

        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $pago = DB::transaction(function () use ($data) {
            $cotizaciones = $data['cotizaciones'];
            unset($data['cotizaciones']);

            $pago = Pago::create($data);

            foreach ($cotizaciones as $detalle) {
                PagoCotizacion::create([
                    'id_pago' => $pago->id,
                    'id_cotizacion' => $detalle['id_cotizacion'],
                    'monto_asignado' => $detalle['monto_asignado'],
                ]);
            }

            return $pago;
        });

        foreach ($data['cotizaciones'] as $detalle) {
            $this->actualizarEstatusCotizacion($detalle['id_cotizacion']);
        }

        return response()->json($pago, 201);
    }

    public function show(Pago $pago)
    {
        // Muestra un pago si no esta marcado como borrado.
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $pago->load('cotizaciones');

        return response()->json($pago);
    }

    public function update(Request $request, Pago $pago)
    {
        // Actualiza un pago y recalcula montos por cotizacion.
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'fecha_pago' => ['sometimes', 'required', 'date'],
            'monto_total' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['sometimes', 'required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['sometimes', 'required', 'string', 'max:255'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
            'cotizaciones' => ['sometimes', 'required', 'array', 'min:1'],
            'cotizaciones.*.id_cotizacion' => ['required', 'exists:cotizaciones,id'],
            'cotizaciones.*.monto_asignado' => ['required', 'numeric', 'min:0.01'],
        ]);

        $cotizaciones = $data['cotizaciones'] ?? null;
        if ($cotizaciones) {
            $montoTotal = $data['monto_total'] ?? $pago->monto_total;
            $sumaAsignada = collect($cotizaciones)->sum('monto_asignado');

            if (abs($sumaAsignada - $montoTotal) > 0.01) {
                return response()->json(['message' => 'La suma asignada debe coincidir con el monto total'], 422);
            }

            foreach ($cotizaciones as $detalle) {
                $totalServicios = $this->totalServiciosCotizacion($detalle['id_cotizacion']);
                if ($totalServicios <= 0) {
                    return response()->json(['message' => 'La cotizacion no tiene servicios asociados'], 422);
                }

                $totalPagado = $this->totalPagadoCotizacion($detalle['id_cotizacion'], $pago->id);
                if (($totalPagado + $detalle['monto_asignado']) > $totalServicios) {
                    return response()->json(['message' => 'El monto supera el total de la cotizacion'], 422);
                }
            }
        }

        DB::transaction(function () use ($data, $pago, $cotizaciones) {
            $updateData = $data;
            unset($updateData['cotizaciones']);

            if (!empty($updateData)) {
                $pago->update($updateData);
            }

            if ($cotizaciones) {
                PagoCotizacion::where('id_pago', $pago->id)->delete();

                foreach ($cotizaciones as $detalle) {
                    PagoCotizacion::create([
                        'id_pago' => $pago->id,
                        'id_cotizacion' => $detalle['id_cotizacion'],
                        'monto_asignado' => $detalle['monto_asignado'],
                    ]);
                }
            }
        });

        $cotizacionesActuales = $cotizaciones
            ? array_column($cotizaciones, 'id_cotizacion')
            : PagoCotizacion::where('id_pago', $pago->id)
                ->pluck('id_cotizacion')
                ->all();

        foreach ($cotizacionesActuales as $idCotizacion) {
            $this->actualizarEstatusCotizacion($idCotizacion);
        }

        return response()->json($pago->fresh('cotizaciones'));
    }

    public function destroy(Pago $pago)
    {
        // Marca el pago como borrado y recalcula el estatus de la cotizacion.
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $pago->update(['borrado_logico' => true]);

        $cotizaciones = PagoCotizacion::where('id_pago', $pago->id)
            ->pluck('id_cotizacion')
            ->all();

        foreach ($cotizaciones as $idCotizacion) {
            $this->actualizarEstatusCotizacion($idCotizacion);
        }

        return response()->json(['message' => 'Deleted']);
    }

    private function totalServiciosCotizacion(int $idCotizacion): float
    {
        return (float) DB::table('servicios_cotizaciones')
            ->join('servicios', 'servicios_cotizaciones.id_servicio', '=', 'servicios.id')
            ->where('servicios_cotizaciones.id_cotizacion', $idCotizacion)
            ->sum('servicios.total_servicio');
    }

    private function actualizarEstatusCotizacion(int $idCotizacion): void
    {
        $totalServicios = $this->totalServiciosCotizacion($idCotizacion);
        $totalPagado = $this->totalPagadoCotizacion($idCotizacion);

        if ($totalServicios <= 0) {
            return;
        }

        if ($totalPagado >= $totalServicios) {
            $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);
            Cotizacion::where('id', $idCotizacion)->update(['estatus' => $estatusPagado->id]);
        }
    }

    private function totalPagadoCotizacion(int $idCotizacion, ?int $excludePagoId = null): float
    {
        $pagos = DB::table('pagos_cotizaciones as pc')
            ->join('pagos as p', 'pc.id_pago', '=', 'p.id')
            ->where('pc.id_cotizacion', $idCotizacion)
            ->where('p.borrado_logico', false);

        if ($excludePagoId) {
            $pagos->where('p.id', '!=', $excludePagoId);
        }

        return (float) $pagos->sum('pc.monto_asignado');
    }
}
