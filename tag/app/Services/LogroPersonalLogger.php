<?php

namespace App\Services;

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
        $idPersonal = self::resolvePersonalId($model);

        $ultimoLogro = LogroPersonal::where('tipo_entidad', $tipoEntidad)
            ->where('id_entidad', $model->getKey())
            ->latest('id')
            ->first();

        $inicio = $ultimoLogro?->created_at ?? $model->getRawOriginal('created_at') ?? now();
        $duracion = max(0, now()->diffInSeconds($inicio));

        LogroPersonal::create([
            'id_personal' => $idPersonal,
            'tipo_entidad' => $tipoEntidad,
            'id_entidad' => $model->getKey(),
            'id_estatus_anterior' => $before,
            'id_estatus_nuevo' => $after,
            'tiempo_transcurrido_segundos' => $duracion,
        ]);
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

    private static function resolvePersonalId(Model $model): ?int
    {
        return match (true) {
            $model instanceof Atencion => $model->id_personal ? (int) $model->id_personal : null,
            $model instanceof Cotizacion => $model->atencion?->id_personal ? (int) $model->atencion->id_personal : null,
            $model instanceof OrdenCompra => $model->cotizacion?->atencion?->id_personal
                ? (int) $model->cotizacion->atencion->id_personal
                : null,
            default => null,
        };
    }
}
