<?php

namespace App\Http\Controllers;

use App\Models\AtencionHistorial;
use App\Models\CotizacionHistorial;
use App\Models\OrdenCompraHistorial;
use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\OrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricasController extends Controller
{
    // Métricas por personal
    /**
     * Obtener métricas por personal
     *
     * @urlParam idPersonal int required ID del miembro del personal. Ejemplo: 1
     */
    public function porPersonal($idPersonal)
    {
        return $this->calcularMetricas($idPersonal);
    }

    // Métricas generales
    public function generales()
    {
        return $this->calcularMetricas();
    }

    // Lógica central de métricas
    private function calcularMetricas($idPersonal = null)
    {
        // Filtros base
        $atenciones = Atencion::query();
        if ($idPersonal) {
            $atenciones->where('id_personal', $idPersonal);
        }
        $atenciones = $atenciones->pluck('id');

        // 1. Tiempo promedio de cambio de estatus en atenciones
        $atencionHist = AtencionHistorial::whereIn('atencion_id', $atenciones)
            ->orderBy('atencion_id')->orderBy('created_at')->get();
        $promedioCambioAtencion = $this->promedioTiempoEntreCambios($atencionHist, 'atencion_id');

        // 2. Tiempo promedio de cambio de estatus en cotizaciones
        $cotizaciones = Cotizacion::whereIn('id_atencion', $atenciones)->pluck('id');
        $cotizacionHist = CotizacionHistorial::whereIn('cotizacion_id', $cotizaciones)
            ->orderBy('cotizacion_id')->orderBy('created_at')->get();
        $promedioCambioCotizacion = $this->promedioTiempoEntreCambios($cotizacionHist, 'cotizacion_id');

        // 3. Tiempo promedio de cambio de estatus en ordenes de compra
        $ordenes = OrdenCompra::whereIn('id_cotizacion', $cotizaciones)->pluck('id');
        $ordenHist = OrdenCompraHistorial::whereIn('orden_compra_id', $ordenes)
            ->orderBy('orden_compra_id')->orderBy('created_at')->get();
        $promedioCambioOrden = $this->promedioTiempoEntreCambios($ordenHist, 'orden_compra_id');

        // 4. Promedio de atenciones que terminan en cotización
        $totalAtenciones = count($atenciones);
        $atencionesConCotizacion = Cotizacion::whereIn('id_atencion', $atenciones)->distinct('id_atencion')->count('id_atencion');
        $promedioAtencionCotizacion = $totalAtenciones ? $atencionesConCotizacion / $totalAtenciones : 0;

        // 5. Promedio de atenciones que terminan en orden de compra
        $atencionesConOrden = OrdenCompra::whereIn('id_cotizacion', $cotizaciones)
            ->join('cotizaciones', 'ordenes_compra.id_cotizacion', '=', 'cotizaciones.id')
            ->whereIn('cotizaciones.id_atencion', $atenciones)
            ->distinct('cotizaciones.id_atencion')->count('cotizaciones.id_atencion');
        $promedioAtencionOrden = $totalAtenciones ? $atencionesConOrden / $totalAtenciones : 0;

        // 6. Promedio de cotizaciones que pasan a orden de compra
        $totalCotizaciones = count($cotizaciones);
        $cotizacionesConOrden = OrdenCompra::whereIn('id_cotizacion', $cotizaciones)->distinct('id_cotizacion')->count('id_cotizacion');
        $promedioCotizacionOrden = $totalCotizaciones ? $cotizacionesConOrden / $totalCotizaciones : 0;

        // 7. Promedio de tiempo en el cual las ordenes de compra se terminan de pagar
        $promedioTiempoPagoOrden = $this->promedioTiempoPagoOrden($ordenHist);

        return [
            'promedio_cambio_estatus_atencion_horas' => $promedioCambioAtencion,
            'promedio_cambio_estatus_cotizacion_horas' => $promedioCambioCotizacion,
            'promedio_cambio_estatus_orden_horas' => $promedioCambioOrden,
            'promedio_atenciones_con_cotizacion' => $promedioAtencionCotizacion,
            'promedio_atenciones_con_orden' => $promedioAtencionOrden,
            'promedio_cotizaciones_con_orden' => $promedioCotizacionOrden,
            'promedio_tiempo_pago_orden_horas' => $promedioTiempoPagoOrden,
        ];
    }

    // Calcula el promedio de tiempo entre cambios de estatus
    private function promedioTiempoEntreCambios($historial, $campoId)
    {
        $tiempos = [];
        $prev = [];
        foreach ($historial as $row) {
            $id = $row[$campoId];
            if (isset($prev[$id])) {
                $diff = $row->created_at->diffInSeconds($prev[$id]);
                $tiempos[] = $diff;
            }
            $prev[$id] = $row->created_at;
        }
        if (count($tiempos) === 0) return 0;
        return round(array_sum($tiempos) / count($tiempos) / 3600, 2); // en horas
    }

    // Calcula el promedio de tiempo en el cual las ordenes de compra se terminan de pagar
    private function promedioTiempoPagoOrden($ordenHist)
    {
        // Busca el tiempo entre el primer estatus y el estatus "pagado" para cada orden
        $tiempos = [];
        $porOrden = [];
        foreach ($ordenHist as $row) {
            $id = $row['orden_compra_id'];
            if (!isset($porOrden[$id])) {
                $porOrden[$id] = ['inicio' => $row->created_at, 'fin' => null];
            }
            // Suponiendo que el estatus "pagado" tiene el nombre exacto
            if ($row->estatus_nuevo && ($row->estatusNuevoObj->estatus ?? '') === 'pagado') {
                $porOrden[$id]['fin'] = $row->created_at;
            }
        }
        foreach ($porOrden as $info) {
            if ($info['inicio'] && $info['fin']) {
                $tiempos[] = $info['fin']->diffInSeconds($info['inicio']);
            }
        }
        if (count($tiempos) === 0) return 0;
        return round(array_sum($tiempos) / count($tiempos) / 3600, 2); // en horas
    }
}
