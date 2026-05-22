<?php

namespace App\Services;

use App\DTOs\CambioEstado;
use App\Models\Atencion;
use App\Models\EstadoAtencion;
use App\Models\EtapaComercial;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de estado para el modelo Atencion.
 *
 * Responsabilidad única: evaluar relaciones de la Atención y asignar
 * la fase comercial (atencion → cotizada → orden_compra) y el cierre
 * (ganada / perdida / reapertura).
 */
class AtencionStateService
{
    /**
     * Sincroniza la fase comercial y estado de cierre de una Atención.
     *
     * @return object{etapa: CambioEstado, estatus: CambioEstado}
     */
    public static function sincronizarFase(Atencion $atencion): object
    {
        $tieneCotizaciones = $atencion->cotizaciones()->exists();

        // Excluir OC anuladas: una OC anulada no cuenta como "tener orden de compra"
        $idAnulada = Cache::remember('catalog.estado_orden_compra.anulada_id', 86400,
            fn () => \App\Models\EstadoOrdenCompra::where('slug', 'anulada')->value('id'));

        $tieneOrdenes = $tieneCotizaciones
            && OrdenCompra::whereIn('id_cotizacion', $atencion->cotizaciones()->pluck('id'))
                ->when($idAnulada, fn ($q) => $q->where('id_estado_orden_compra', '!=', $idAnulada))
                ->exists();

        $slugFase = $tieneOrdenes ? 'orden_compra' : ($tieneCotizaciones ? 'cotizada' : 'atencion');

        $etapa = Cache::remember("catalog.etapa_comercial.{$slugFase}", 86400,
            fn () => EtapaComercial::where('slug', $slugFase)->first());

        Log::info("AtencionStateService: Atencion #{$atencion->id} → fase={$slugFase}");

        if (! $etapa) {
            return (object) ['etapa' => CambioEstado::sinCambio(), 'estatus' => CambioEstado::sinCambio()];
        }

        $cambioEtapa = CambioEstado::sinCambio();
        $huboCambio = false;

        if ($atencion->id_etapa_comercial !== $etapa->id) {
            $cambioEtapa = CambioEstado::conCambio(
                $atencion->id_etapa_comercial, $etapa->id,
                'Cambio de etapa automático por eventos/sistema.'
            );
            $atencion->id_etapa_comercial = $etapa->id;
            $huboCambio = true;
        }

        $cambioEstatus = self::evaluarCierre($atencion, $slugFase, $huboCambio);

        if ($huboCambio) {
            $atencion->save();
        }

        return (object) ['etapa' => $cambioEtapa, 'estatus' => $cambioEstatus];
    }

    private static function evaluarCierre(Atencion $atencion, string $slugFase, bool &$huboCambio): CambioEstado
    {
        if ($slugFase === 'orden_compra') {
            $cerradaGanada = Cache::remember('catalog.estado_atencion.cerrada_ganada', 86400,
                fn () => EstadoAtencion::where('slug', 'cerrada_ganada')->first());

            if ($cerradaGanada && $atencion->id_estado_atencion !== $cerradaGanada->id) {
                $anterior = $atencion->id_estado_atencion;
                $atencion->id_estado_atencion = $cerradaGanada->id;
                $huboCambio = true;

                return CambioEstado::conCambio($anterior, $cerradaGanada->id,
                    'Cerrada ganada automáticamente por Orden de Compra');
            }
        }

        if ($slugFase === 'cotizada') {
            $idRechazada = Cache::remember('catalog.estado_cotizacion.rechazada_id', 86400,
                fn () => \App\Models\EstadoCotizacion::where('slug', 'rechazada')->value('id'));

            $todasRechazadas = $idRechazada
                && $atencion->cotizaciones()->where('id_estado_cotizacion', '!=', $idRechazada)->doesntExist();

            if ($todasRechazadas) {
                $cerradaPerdida = Cache::remember('catalog.estado_atencion.cerrada_perdida', 86400,
                    fn () => EstadoAtencion::where('slug', 'cerrada_perdida')->first());

                if ($cerradaPerdida && $atencion->id_estado_atencion !== $cerradaPerdida->id) {
                    $anterior = $atencion->id_estado_atencion;
                    $atencion->id_estado_atencion = $cerradaPerdida->id;
                    $huboCambio = true;

                    return CambioEstado::conCambio($anterior, $cerradaPerdida->id,
                        'Cerrada perdida automáticamente: todas las cotizaciones rechazadas');
                }
            } else {
                $abierta = Cache::remember('catalog.estado_atencion.abierta', 86400,
                    fn () => EstadoAtencion::where('slug', 'abierta')->first());
                $idCerradaPerdida = Cache::remember('catalog.estado_atencion.cerrada_perdida_id', 86400,
                    fn () => EstadoAtencion::where('slug', 'cerrada_perdida')->value('id'));

                if ($abierta && $atencion->id_estado_atencion === $idCerradaPerdida) {
                    $anterior = $atencion->id_estado_atencion;
                    $atencion->id_estado_atencion = $abierta->id;
                    $huboCambio = true;

                    return CambioEstado::conCambio($anterior, $abierta->id,
                        'Reabierta automáticamente: nueva cotización activa');
                }
            }
        }

        return CambioEstado::sinCambio();
    }
}
