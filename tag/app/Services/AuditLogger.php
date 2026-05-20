<?php

namespace App\Services;

use App\Jobs\PersistirAuditLog;
use App\Models\AuditLog;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    private static array $beforeUpdate = [];

    private static array $beforeDelete = [];

    private static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'clave', // Agregado por traducción
    ];

    public static function captureBeforeUpdate(Model $model): void
    {
        self::$beforeUpdate[spl_object_id($model)] = $model->getOriginal();
    }

    public static function captureBeforeDelete(Model $model): void
    {
        self::$beforeDelete[spl_object_id($model)] = $model->getAttributes();
    }

    public static function logModelCreated(Model $model): void
    {
        if (! self::shouldAuditModel($model)) {
            return;
        }

        self::write([
            'action' => 'CREATE',
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'before_data' => null,
            'after_data' => self::sanitizeArray($model->getAttributes()),
            'success' => true,
        ]);
    }

    public static function logModelUpdated(Model $model): void
    {
        if (! self::shouldAuditModel($model)) {
            return;
        }

        $objectId = spl_object_id($model);
        $before = self::$beforeUpdate[$objectId] ?? [];
        unset(self::$beforeUpdate[$objectId]);

        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $beforeDiff = [];
        $afterDiff = [];

        foreach (array_keys($changes) as $key) {
            $beforeDiff[$key] = $before[$key] ?? null;
            $afterDiff[$key] = $model->getAttribute($key);
        }

        self::write([
            'action' => 'UPDATE',
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'before_data' => self::sanitizeArray($beforeDiff),
            'after_data' => self::sanitizeArray($afterDiff),
            'success' => true,
        ]);
    }

    public static function logModelDeleted(Model $model): void
    {
        if (! self::shouldAuditModel($model)) {
            return;
        }

        $objectId = spl_object_id($model);
        $before = self::$beforeDelete[$objectId] ?? $model->getAttributes();
        unset(self::$beforeDelete[$objectId]);

        self::write([
            'action' => 'DELETE',
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'before_data' => self::sanitizeArray($before),
            'after_data' => null,
            'success' => true,
        ]);
    }

    public static function logAuthEvent(
        string $action,
        Request $request,
        ?Usuario $usuario,
        bool $success,
        ?string $message = null,
        array $afterData = [],
        ?int $statusCode = null
    ): void {
        // Auth events son siempre síncronos: se deben persistir en el mismo request
        // (login fallido, token inválido, etc. deben quedar registrados inmediatamente)
        self::write([
            'usuario_id' => $usuario?->id,
            'user_role' => $usuario?->roles()->pluck('name')->implode(',') ?: null,
            'action' => $action,
            'table_name' => 'usuarios',
            'record_id' => $usuario?->id,
            'before_data' => null,
            'after_data' => self::sanitizeArray($afterData),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->path(),
            'http_method' => $request->method(),
            'status_code' => $statusCode,
            'success' => $success,
            'message' => $message,
        ], async: false);
    }

    private static function shouldAuditModel(Model $model): bool
    {
        if ($model instanceof AuditLog) {
            return false;
        }

        return str_starts_with($model::class, 'App\\Models\\');
    }

    private static function sanitizeArray(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as $key => $value) {
            if (in_array((string) $key, self::$sensitiveKeys, true)) {
                $data[$key] = '[CHANGED]';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::sanitizeArray($value);
            }
        }

        return $data;
    }

    private static function write(array $data, bool $async = true): void
    {
        $request = request();
        $actor = Auth::user();

        $payload = array_merge([
            'usuario_id' => $actor?->id,
            'user_role' => $actor?->roles()->pluck('name')->implode(',') ?: null,
            'table_name' => null,
            'record_id' => null,
            'before_data' => null,
            'after_data' => null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'route' => $request?->path(),
            'http_method' => $request?->method(),
            'status_code' => null,
            'success' => true,
            'message' => null,
        ], $data);

        if ($async) {
            // CRUD models → queue async: no bloquea el request HTTP
            PersistirAuditLog::dispatch($payload);
        } else {
            // Auth events → síncrono: deben persistirse incluso si el request falla
            AuditLog::create($payload);
        }
    }
}
