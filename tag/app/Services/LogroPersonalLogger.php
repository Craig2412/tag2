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
        if (! self::isTrackable($model)) {
            return;
        }

        $objectId = spl_object_id($model);
        self::$beforeStatus[$objectId] = self::resolveSlug($model, useOriginal: true);
    }

    public static function logStatusChange(Model $model): void
    {
        if (! self::isTrackable($model)) {
            return;
        }

        $objectId = spl_object_id($model);
        $before = self::$beforeStatus[$objectId] ?? null;
        unset(self::$beforeStatus[$objectId]);

        if ($before === null) {
            return;
        }

        $after = self::resolveSlug($model, useOriginal: false);
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

        $inicio = $ultimoLogro ?? ($model->getRawOriginal('created_at') ?? now());
        $duracion = max(0, now()->diffInSeconds($inicio));

        PersistirLogroPersonal::dispatch([
            'id_personal' => $idPersonal,
            'tipo_entidad' => $tipoEntidad,
            'id_entidad' => $model->getKey(),
            'estatus_anterior' => $before,
            'estatus_nuevo' => $after,
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
            $model instanceof Atencion => 'atencion',
            $model instanceof Cotizacion => 'cotizacion',
            default => 'orden_compra',
        };
    }

    /**
     * Resuelve el slug del estado actual (o anterior) del modelo.
     * Cada modelo usa un campo y tabla de estados distinta.
     */
    private static function resolveSlug(Model $model, bool $useOriginal): ?string
    {
        if ($model instanceof Atencion) {
            $id = $useOriginal
                ? $model->getRawOriginal('id_estado_atencion')
                : $model->getAttribute('id_estado_atencion');

            return \App\Models\EstadoAtencion::find($id)?->slug;
        }

        if ($model instanceof Cotizacion) {
            $id = $useOriginal
                ? $model->getRawOriginal('id_estado_cotizacion')
                : $model->getAttribute('id_estado_cotizacion');

            return \App\Models\EstadoCotizacion::find($id)?->slug;
        }

        if ($model instanceof OrdenCompra) {
            $id = $useOriginal
                ? $model->getRawOriginal('id_estado_orden_compra')
                : $model->getAttribute('id_estado_orden_compra');

            return \App\Models\EstadoOrdenCompra::find($id)?->slug;
        }

        return null;
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
