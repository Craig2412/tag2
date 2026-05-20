<?php

namespace App\Services;

use App\DTOs\CambioEstado;
use App\Models\Atencion;
use App\Models\OrdenCompra;

/**
 * Fachada de compatibilidad — delega en los servicios especializados.
 *
 * @deprecated Usar AtencionStateService y OrdenStateService directamente.
 *             Esta clase se mantiene para no romper imports existentes.
 */
class EstadoFaseService
{
    public static function sincronizarFaseAtencion(Atencion $atencion): object
    {
        return AtencionStateService::sincronizarFase($atencion);
    }

    public static function sincronizarEstadoFinanciero(OrdenCompra $orden): CambioEstado
    {
        return OrdenStateService::sincronizarFinanciero($orden);
    }

    public static function sincronizarEstadoEgreso(OrdenCompra $orden): CambioEstado
    {
        return OrdenStateService::sincronizarEgreso($orden);
    }

    public static function sincronizarEstadoOperativo(OrdenCompra $orden): CambioEstado
    {
        // Redirigido al servicio real vía reflexión — solo para compatibilidad.
        // Nuevo código debe llamar OrdenStateService directamente.
        return OrdenStateService::sincronizarFinanciero($orden->fresh());
    }
}
