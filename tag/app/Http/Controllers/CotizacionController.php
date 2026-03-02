<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use App\Models\PagoCotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    public function index()
    {
        // Lista las cotizaciones activas y las devuelve en JSON.
        Cotizacion::expirarSiVence();

        $items = Cotizacion::where('borrado_logico', false)
            ->orderBy('id')
            ->get()
            ->map(function (Cotizacion $cotizacion) {
                $data = $cotizacion->toArray();
                $data['pagos'] = $this->pagosUnificados($cotizacion);

                return $data;
            })
            ->values();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        // Crea una cotizacion con estatus inicial y la devuelve.
        $data = $request->validate([
            'id_atencion' => ['required', 'exists:atenciones,id'],
            'id_tipo_cotizacion' => ['required', 'exists:tipos_cotizaciones,id'],
            'cant_adultos' => ['required', 'integer', 'min:0'],
            'cant_menores' => ['required', 'integer', 'min:0'],
            'cant_viejos' => ['required', 'integer', 'min:0'],
            'id_tasa_asignada' => ['required', 'exists:tasas,id'],
            'id_tasa_cambio' => ['required', 'exists:tasas_cambio,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $estatus = Estatus::firstOrCreate(['estatus' => 'por pagar']);

        $data['estatus'] = $estatus->id;
        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $item = Cotizacion::create($data);

        return response()->json($item, 201);
    }

    public function show(Cotizacion $cotizacion)
    {
        // Muestra una cotizacion si no esta marcada como borrada.
        Cotizacion::expirarSiVence();
        $cotizacion->refresh();

        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $cotizacion->toArray();
        $data['pagos'] = $this->pagosUnificados($cotizacion);

        return response()->json($data);
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        // Actualiza una cotizacion activa y devuelve el resultado.
        Cotizacion::expirarSiVence();
        $cotizacion->refresh();

        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $estatusExpirado = Estatus::firstOrCreate(['estatus' => 'expirado']);
        if ($cotizacion->estatus === $estatusExpirado->id) {
            return response()->json(['message' => 'La cotizacion esta expirada y no puede modificarse'], 422);
        }

        $data = $request->validate([
            'id_atencion' => ['sometimes', 'required', 'exists:atenciones,id'],
            'id_tipo_cotizacion' => ['sometimes', 'required', 'exists:tipos_cotizaciones,id'],
            'cant_adultos' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_menores' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_viejos' => ['sometimes', 'required', 'integer', 'min:0'],
            'id_tasa_asignada' => ['sometimes', 'required', 'exists:tasas,id'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $cotizacion->update($data);

        return response()->json($cotizacion);
    }

    public function destroy(Cotizacion $cotizacion)
    {
        // Marca la cotizacion como borrada de forma logica.
        Cotizacion::expirarSiVence();
        $cotizacion->refresh();

        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $estatusExpirado = Estatus::firstOrCreate(['estatus' => 'expirado']);
        if ($cotizacion->estatus === $estatusExpirado->id) {
            return response()->json(['message' => 'La cotizacion esta expirada y no puede modificarse'], 422);
        }

        $cotizacion->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }

    private function pagosUnificados(Cotizacion $cotizacion): array
    {
        $pagos = DB::table('pagos_cotizaciones as pc')
            ->join('pagos as p', 'pc.id_pago', '=', 'p.id')
            ->where('pc.id_cotizacion', $cotizacion->id)
            ->where('p.borrado_logico', false)
            ->orderBy('p.fecha_pago')
            ->orderBy('pc.id')
            ->get([
                'pc.id',
                'pc.monto_asignado',
                'p.id as id_pago',
                'p.fecha_pago',
                'p.id_metodo_pago',
                'p.nro_comprobante',
                'p.id_tasa_cambio',
                'p.estatus',
            ])
            ->map(function ($pago) {
                return [
                    'id' => $pago->id,
                    'id_pago' => $pago->id_pago,
                    'monto' => (float) $pago->monto_asignado,
                    'fecha_pago' => $pago->fecha_pago,
                    'id_metodo_pago' => $pago->id_metodo_pago,
                    'nro_comprobante' => $pago->nro_comprobante,
                    'id_tasa_cambio' => $pago->id_tasa_cambio,
                    'estatus' => $pago->estatus,
                ];
            })
            ->all();

        return $pagos;
    }
}
