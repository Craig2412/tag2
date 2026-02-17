<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Estatus;
use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    public function index()
    {
        $items = Cotizacion::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_atencion' => ['required', 'exists:atenciones,id'],
            'cant_adultos' => ['required', 'integer', 'min:0'],
            'cant_menores' => ['required', 'integer', 'min:0'],
            'cant_viejos' => ['required', 'integer', 'min:0'],
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
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($cotizacion);
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'id_atencion' => ['sometimes', 'required', 'exists:atenciones,id'],
            'cant_adultos' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_menores' => ['sometimes', 'required', 'integer', 'min:0'],
            'cant_viejos' => ['sometimes', 'required', 'integer', 'min:0'],
            'id_tasa_cambio' => ['sometimes', 'required', 'exists:tasas_cambio,id'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $cotizacion->update($data);

        return response()->json($cotizacion);
    }

    public function destroy(Cotizacion $cotizacion)
    {
        if ($cotizacion->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $cotizacion->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
    }
}
