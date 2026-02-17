<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\Pago;
use App\Models\ServicioCotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function index()
    {
        $items = Pago::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cotizacion' => ['required', 'exists:cotizaciones,id'],
            'fecha_pago' => ['required', 'date'],
            'monto_abono' => ['required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['required', 'string', 'max:255'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
            'estatus' => ['required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $totalServicios = $this->totalServiciosCotizacion($data['id_cotizacion']);

        if ($totalServicios <= 0) {
            return response()->json(['message' => 'La cotizacion no tiene servicios asociados'], 422);
        }

        $abonos = Pago::where('id_cotizacion', $data['id_cotizacion'])
            ->where('borrado_logico', false)
            ->sum('monto_abono');

        if (($abonos + $data['monto_abono']) > $totalServicios) {
            return response()->json(['message' => 'El monto supera el total de la cotizacion'], 422);
        }

        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $pago = Pago::create($data);

        $this->actualizarEstatusCotizacion($data['id_cotizacion']);

        return response()->json($pago, 201);
    }

    public function show(Pago $pago)
    {
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($pago);
    }

    public function update(Request $request, Pago $pago)
    {
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'id_cotizacion' => ['sometimes', 'required', 'exists:cotizaciones,id'],
            'fecha_pago' => ['sometimes', 'required', 'date'],
            'monto_abono' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['sometimes', 'required', 'exists:metodos_pago,id'],
            'nro_comprobante' => ['sometimes', 'required', 'string', 'max:255'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $idCotizacion = $data['id_cotizacion'] ?? $pago->id_cotizacion;
        $nuevoMonto = $data['monto_abono'] ?? $pago->monto_abono;

        $totalServicios = $this->totalServiciosCotizacion($idCotizacion);

        if ($totalServicios <= 0) {
            return response()->json(['message' => 'La cotizacion no tiene servicios asociados'], 422);
        }

        $abonos = Pago::where('id_cotizacion', $idCotizacion)
            ->where('borrado_logico', false)
            ->where('id', '!=', $pago->id)
            ->sum('monto_abono');

        if (($abonos + $nuevoMonto) > $totalServicios) {
            return response()->json(['message' => 'El monto supera el total de la cotizacion'], 422);
        }

        $pago->update($data);

        $this->actualizarEstatusCotizacion($idCotizacion);

        return response()->json($pago);
    }

    public function destroy(Pago $pago)
    {
        if ($pago->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $pago->update(['borrado_logico' => true]);

        $this->actualizarEstatusCotizacion($pago->id_cotizacion);

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
        $totalPagado = Pago::where('id_cotizacion', $idCotizacion)
            ->where('borrado_logico', false)
            ->sum('monto_abono');

        if ($totalServicios <= 0) {
            return;
        }

        if ($totalPagado >= $totalServicios) {
            $estatusPagado = Estatus::firstOrCreate(['estatus' => 'pagado']);
            Cotizacion::where('id', $idCotizacion)->update(['estatus' => $estatusPagado->id]);
        }
    }
}
