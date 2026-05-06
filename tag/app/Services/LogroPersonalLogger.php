<?php

namespace App\Services;

use App\Jobs\PersistirLogroPersonal;
use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\LogroPersonal;
use App\Models\OrdenCompra;
use Illuminate\Database\Eloquent\Model;

class LogroPersonalLogger
{
    private static array $beforeStatus = [];

    public static function captureBeforeUpdate(Model $model): void
    {
        if (!self::isTrackable($model)) {
            return;
        }

        $objectId = spl_object_id($model);
        self::$beforeStatus[$objectId] = (int) $model->getRawOriginal('estatus');
    }

    public static function logStatusChange(Model $model): void
    {
        if (!self::isTrackable($model)) {
            return;
        }

        $objectId = spl_object_id($model);
        $before = self::$beforeStatus[$objectId] ?? null;
        unset(self::$beforeStatus[$objectId]);

        if ($before === null) {
            return;
        }

        $after = (int) $model->getAttribute('estatus');
        if ($before === $after) {
            return;
        }

        $tipoEntidad = self::resolveTipoEntidad($model);

        // Resolvemos el id_personal ANTES de encolar, precargando relaciones
        // para evitar consultas N+1 lazy dentro del Job.
        $idPersonal = self::resolvePersonalId($model);

        // Buscamos el último logro también aquí para calcular duración correctamente.
        // Usamos datos primitivos en el payload para que sea serialization-safe.
        $ultimoLogro = LogroPersonal::where('tipo_entidad', $tipoEntidad)
            ->where('id_entidad', $model->getKey())
            ->latest('id')
            ->value('created_at');

        $inicio   = $ultimoLogro ?? ($model->getRawOriginal('created_at') ?? now());
        $duracion = max(0, now()->diffInSeconds($inicio));

        PersistirLogroPersonal::dispatch([
            'id_personal'                  => $idPersonal,
            'tipo_entidad'                 => $tipoEntidad,
            'id_entidad'                   => $model->getKey(),
            'id_estatus_anterior'          => $before,
            'id_estatus_nuevo'             => $after,
            'tiempo_transcurrido_segundos' => $duracion,
        ], auth()->id());
    }

    private static function isTrackable(Model $model): bool
    {
        return $model instanceof Atencion
            || $model instanceof Cotizacion
            || $model instanceof OrdenCompra;
    }

    private static function resolveTipoEntidad(Model $model): string
    {
        return match (true) {
            $model instanceof Atencion   => 'atencion',
            $model instanceof Cotizacion => 'cotizacion',
            default                      => 'orden_compra',
        };
    }

    /**
     * Resuelve el id_personal precargando relaciones faltantes con loadMissing()
     * para evitar consultas N+1 al acceder a relaciones lazy.
     */
    private static function resolvePersonalId(Model $model): ?int
    {
        if ($model instanceof Atencion) {
            return $model->id_personal ? (int) $model->id_personal : null;
        }

        if ($model instanceof Cotizacion) {
            // Precarga la relación si aún no fue cargada en el request
            $model->loadMissing('atencion');
            return $model->atencion?->id_personal
                ? (int) $model->atencion->id_personal
                : null;
        }

        if ($model instanceof OrdenCompra) {
            // Precarga la cadena completa si no está en memoria
            $model->loadMissing('cotizacion.atencion');
            return $model->cotizacion?->atencion?->id_personal
                ? (int) $model->cotizacion->atencion->id_personal
                : null;
        }

        return null;
    }
}
