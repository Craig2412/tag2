<?php

namespace App\Http\Controllers;

use App\Models\ServicioCotizacion;
use Illuminate\Http\Request;

class ServicioCotizacionController extends Controller
{
    public function index()
    {
        // Lista las relaciones servicio-cotizacion y las devuelve.
        return response()->json(ServicioCotizacion::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        // Asocia un servicio con una cotizacion y devuelve el registro.
        $data = $request->validate([
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_cotizacion' => ['required', 'exists:cotizaciones,id'],
        ]);

        $item = ServicioCotizacion::create($data);

        return response()->json($item, 201);
    }

    public function show(ServicioCotizacion $servicioCotizacion)
    {
        // Muestra una relacion servicio-cotizacion por id.
        return response()->json($servicioCotizacion);
    }

    public function update(Request $request, ServicioCotizacion $servicioCotizacion)
    {
        // Actualiza la relacion entre servicio y cotizacion.
        $data = $request->validate([
            'id_servicio' => ['sometimes', 'required', 'exists:servicios,id'],
            'id_cotizacion' => ['sometimes', 'required', 'exists:cotizaciones,id'],
        ]);

        $servicioCotizacion->update($data);

        return response()->json($servicioCotizacion);
    }

    public function destroy(ServicioCotizacion $servicioCotizacion)
    {
        // Elimina la relacion servicio-cotizacion y confirma.
        $servicioCotizacion->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
