<?php

namespace App\Providers;

use App\Services\AuditLogger;
use App\Services\LogroPersonalLogger;
use App\Models\Servicio;
use App\Models\PagoOrdenCompra;
use App\Models\Cotizacion;
use App\Models\OrdenCompra;
use App\Observers\ServicioObserver;
use App\Observers\PagoOrdenCompraObserver;
use App\Observers\CotizacionObserver;
use App\Observers\OrdenCompraObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Listeners\BroadcastPermissionsChanged;
use Spatie\Permission\Events\RoleAssigned;
use Spatie\Permission\Events\RoleRemoved;
use Spatie\Permission\Events\PermissionGranted;
use Spatie\Permission\Events\PermissionRevoked;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Servicio::observe(ServicioObserver::class);
        PagoOrdenCompra::observe(PagoOrdenCompraObserver::class);
        Cotizacion::observe(CotizacionObserver::class);
        OrdenCompra::observe(OrdenCompraObserver::class);

        Event::listen('eloquent.updating: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;

            if ($model instanceof Model) {
                AuditLogger::captureBeforeUpdate($model);
                LogroPersonalLogger::captureBeforeUpdate($model);
            }
        });

        Event::listen('eloquent.deleting: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;

            if ($model instanceof Model) {
                AuditLogger::captureBeforeDelete($model);
            }
        });

        Event::listen('eloquent.created: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;

            if ($model instanceof Model) {
                AuditLogger::logModelCreated($model);
            }
        });

        Event::listen('eloquent.updated: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;

            if ($model instanceof Model) {
                AuditLogger::logModelUpdated($model);
                LogroPersonalLogger::logStatusChange($model);
            }
        });

        Event::listen('eloquent.deleted: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;

            if ($model instanceof Model) {
                AuditLogger::logModelDeleted($model);
            }
        });

        // Register listeners for Spatie Permission events to sync session via WebSockets
        Event::listen(RoleAssigned::class, BroadcastPermissionsChanged::class);
        Event::listen(RoleRemoved::class, BroadcastPermissionsChanged::class);
        Event::listen(PermissionGranted::class, BroadcastPermissionsChanged::class);
        Event::listen(PermissionRevoked::class, BroadcastPermissionsChanged::class);
    }
}
