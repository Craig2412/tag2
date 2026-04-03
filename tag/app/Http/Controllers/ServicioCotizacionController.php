<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
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
        $this->recalcularOrdenCompraPorCotizacion($item->id_cotizacion);

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

        $idCotizacionAnterior = $servicioCotizacion->id_cotizacion;

        $servicioCotizacion->update($data);
        $this->recalcularOrdenCompraPorCotizacion($idCotizacionAnterior);
        $this->recalcularOrdenCompraPorCotizacion($servicioCotizacion->id_cotizacion);

        return response()->json($servicioCotizacion);
    }

    public function destroy(ServicioCotizacion $servicioCotizacion)
    {
        // Elimina la relacion servicio-cotizacion y confirma.
        $idCotizacion = $servicioCotizacion->id_cotizacion;
        $servicioCotizacion->delete();
        $this->recalcularOrdenCompraPorCotizacion($idCotizacion);

        return response()->json(['message' => 'Deleted']);
    }

    private function recalcularOrdenCompraPorCotizacion(int $idCotizacion): void
    {
        $ordenCompra = OrdenCompra::where('id_cotizacion', $idCotizacion)->first();
        $ordenCompra?->recalcularMontoTotal();
    }
}
