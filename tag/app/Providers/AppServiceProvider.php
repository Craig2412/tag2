<?php

namespace App\Providers;

use App\Services\AuditLogger;
use App\Services\LogroPersonalLogger;
use App\Models\Servicio;
use App\Models\PagoOrdenCompra;
use App\Observers\ServicioObserver;
use App\Observers\PagoOrdenCompraObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
    }
}
