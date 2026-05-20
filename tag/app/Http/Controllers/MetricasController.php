<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricasController extends Controller
{
    /**
     * Obtener métricas por personal
     *
     * @urlParam idPersonal int required ID del miembro del personal. Ejemplo: 1
     */
    public function porPersonal($idPersonal)
    {
        return $this->calcularMetricas($idPersonal);
    }

    /** Métricas generales (todos los usuarios) */
    public function generales()
    {
        return $this->calcularMetricas();
    }

    /**
     * Lógica central de métricas usando SQL agregadas en vez de cargar todo en memoria.
     */
    private function calcularMetricas($idPersonal = null): array
    {
        // ── Filtros base ──────────────────────────────────────────
        $atencionQuery = Atencion::withTrashed();
        if ($idPersonal) {
            $atencionQuery->where('id_personal', $idPersonal);
        }
        $idsAtenciones = $atencionQuery->pluck('id');

        if ($idsAtenciones->isEmpty()) {
            return $this->metricasVacias();
        }

        $idsCotizaciones = Cotizacion::whereIn('id_atencion', $idsAtenciones)->pluck('id');
        $idsOrdenes = OrdenCompra::whereIn('id_cotizacion', $idsCotizaciones)->pluck('id');

        // ── 1-3. Tiempos promedio entre cambios (SQL con LAG) ─────
        $promedioCambioAtencion = $this->promedioTiempoEntreCambiosSQL('atencion_historial', 'atencion_id', $idsAtenciones);
        $promedioCambioCotizacion = $this->promedioTiempoEntreCambiosSQL('cotizacion_historial', 'cotizacion_id', $idsCotizaciones);
        $promedioCambioOrden = $this->promedioTiempoEntreCambiosSQL('orden_compra_historial', 'orden_compra_id', $idsOrdenes);

        // ── 4-6. Ratios de conversión (COUNT DISTINCT) ────────────
        $totalAtenciones = $idsAtenciones->count();
        $atencionesConCotizacion = Cotizacion::whereIn('id_atencion', $idsAtenciones)->distinct('id_atencion')->count('id_atencion');
        $totalCotizaciones = $idsCotizaciones->count();
        $cotizacionesConOrden = OrdenCompra::whereIn('id_cotizacion', $idsCotizaciones)->distinct('id_cotizacion')->count('id_cotizacion');
        $atencionesConOrden = $idsOrdenes->isNotEmpty()
            ? OrdenCompra::whereIn('id_cotizacion', $idsCotizaciones)
                ->join('cotizaciones', 'ordenes_compra.id_cotizacion', '=', 'cotizaciones.id')
                ->whereIn('cotizaciones.id_atencion', $idsAtenciones)
                ->distinct('cotizaciones.id_atencion')
                ->count('cotizaciones.id_atencion')
            : 0;

        // ── 7. Tiempo promedio hasta liquidación ──────────────────
        $promedioTiempoPagoOrden = $this->promedioTiempoPagoOrdenSQL($idsOrdenes);

        return [
            'promedio_cambio_estatus_atencion_horas' => $promedioCambioAtencion,
            'promedio_cambio_estatus_cotizacion_horas' => $promedioCambioCotizacion,
            'promedio_cambio_estatus_orden_horas' => $promedioCambioOrden,
            'promedio_atenciones_con_cotizacion' => $totalAtenciones ? round($atencionesConCotizacion / $totalAtenciones, 4) : 0,
            'promedio_atenciones_con_orden' => $totalAtenciones ? round($atencionesConOrden / $totalAtenciones, 4) : 0,
            'promedio_cotizaciones_con_orden' => $totalCotizaciones ? round($cotizacionesConOrden / $totalCotizaciones, 4) : 0,
            'promedio_tiempo_pago_orden_horas' => $promedioTiempoPagoOrden,
        ];
    }

    /**
     * Calcula el tiempo promedio entre cambios de estado usando SQL con LAG().
     * Evita cargar todos los registros en memoria PHP.
     */
    private function promedioTiempoEntreCambiosSQL(string $tabla, string $columnaId, $ids): float
    {
        if ($ids instanceof \Illuminate\Support\Collection) {
            $ids = $ids->toArray();
        }
        if (empty($ids)) {
            return 0.0;
        }

        $idsStr = implode(',', array_map('intval', $ids));

        $result = DB::select("
            SELECT COALESCE(AVG(diff_segundos), 0) / 3600 AS promedio_horas
            FROM (
                SELECT
                    TIMESTAMPDIFF(SECOND,
                        LAG(created_at) OVER (PARTITION BY {$columnaId} ORDER BY created_at),
                        created_at
                    ) AS diff_segundos
                FROM {$tabla}
                WHERE {$columnaId} IN ({$idsStr})
            ) sub
            WHERE diff_segundos IS NOT NULL
        ");

        return round((float) ($result[0]->promedio_horas ?? 0), 2);
    }

    /**
     * Calcula el tiempo promedio que tarda una OC en llegar al estado operativo "completada".
     * Usa SQL agregadas con el ID de estado cacheado.
     */
    private function promedioTiempoPagoOrdenSQL($idsOrdenes): float
    {
        if ($idsOrdenes instanceof \Illuminate\Support\Collection) {
            $idsOrdenes = $idsOrdenes->toArray();
        }
        if (empty($idsOrdenes)) {
            return 0.0;
        }

        $idsStr = implode(',', array_map('intval', $idsOrdenes));
        $idCompletada = Cache::remember('catalog.estado_orden_compra.completada_id', 86400, function () {
            return \App\Models\EstadoOrdenCompra::where('slug', 'completada')->value('id');
        });

        if (! $idCompletada) {
            return 0.0;
        }

        $result = DB::select("
            SELECT COALESCE(AVG(TIMESTAMPDIFF(SECOND, firsts.inicio, comps.fin)), 0) / 3600 AS promedio_horas
            FROM (
                SELECT orden_compra_id, MIN(created_at) AS inicio
                FROM orden_compra_historial
                WHERE orden_compra_id IN ({$idsStr})
                GROUP BY orden_compra_id
            ) firsts
            INNER JOIN (
                SELECT orden_compra_id, MIN(created_at) AS fin
                FROM orden_compra_historial
                WHERE orden_compra_id IN ({$idsStr})
                  AND id_estado_nuevo = {$idCompletada}
                GROUP BY orden_compra_id
            ) comps ON firsts.orden_compra_id = comps.orden_compra_id
        ");

        return round((float) ($result[0]->promedio_horas ?? 0), 2);
    }

    private function metricasVacias(): array
    {
        return [
            'promedio_cambio_estatus_atencion_horas' => 0,
            'promedio_cambio_estatus_cotizacion_horas' => 0,
            'promedio_cambio_estatus_orden_horas' => 0,
            'promedio_atenciones_con_cotizacion' => 0,
            'promedio_atenciones_con_orden' => 0,
            'promedio_cotizaciones_con_orden' => 0,
            'promedio_tiempo_pago_orden_horas' => 0,
        ];
    }
}
