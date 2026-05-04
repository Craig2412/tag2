<?php

namespace App\Services;

use App\Models\Estatus;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve IDs de estatus por nombre desde el catálogo.
 *
 * Centraliza el acceso al catálogo de estatus para evitar llamadas
 * a firstOrCreate() dispersas en los controllers, que pueden crear
 * duplicados con errores tipográficos en producción.
 *
 * Uso:
 *   EstatusResolver::id('aprobada')     → int|null
 *   EstatusResolver::idOrFail('pagado') → int (lanza si no existe)
 */
class EstatusResolver
{
    /**
     * Retorna el ID del estatus dado su nombre exacto.
     * Retorna null si no existe en el catálogo.
     */
    public static function id(string $nombre): ?int
    {
        return Cache::remember("estatus_id_{$nombre}", 300, function () use ($nombre) {
            return Estatus::where('estatus', $nombre)->value('id');
        });
    }

    /**
     * Retorna el ID del estatus o lanza una excepción si no existe.
     * Útil en observers/listeners donde un estatus faltante es un error de configuración.
     */
    public static function idOrFail(string $nombre): int
    {
        $id = self::id($nombre);

        if ($id === null) {
            throw new \RuntimeException(
                "El estatus '{$nombre}' no existe en el catálogo. Verifica los seeders."
            );
        }

        return $id;
    }
}
