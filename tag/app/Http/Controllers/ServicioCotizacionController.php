<?php

namespace App\Http\Controllers;

use App\Models\ServicioCotizacion;
use Illuminate\Http\Request;

class ServicioCotizacionController extends Controller
{
    public function index()
    {
        return response()->json(ServicioCotizacion::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_cotizacion' => ['required', 'exists:cotizaciones,id'],
        ]);

        $item = ServicioCotizacion::create($data);

        return response()->json($item, 201);
    }

    public function show(ServicioCotizacion $servicioCotizacion)
    {
        return response()->json($servicioCotizacion);
    }

    public function update(Request $request, ServicioCotizacion $servicioCotizacion)
    {
        $data = $request->validate([
            'id_servicio' => ['sometimes', 'required', 'exists:servicios,id'],
            'id_cotizacion' => ['sometimes', 'required', 'exists:cotizaciones,id'],
        ]);

        $servicioCotizacion->update($data);

        return response()->json($servicioCotizacion);
    }

    public function destroy(ServicioCotizacion $servicioCotizacion)
    {
        $servicioCotizacion->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
