<?php

namespace App\Providers;

use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Models\Servicio;
use App\Observers\AtencionObserver;
use App\Observers\CotizacionObserver;
use App\Observers\OrdenCompraObserver;
use App\Observers\PagoObserver;
use App\Observers\PagoOrdenCompraObserver;
use App\Observers\PagoProveedorCuentaObserver;
use App\Observers\PagoProveedorObserver;
use App\Observers\ServicioObserver;
use App\Services\AuditLogger;
use App\Services\LogroPersonalLogger;
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
        // ── Observers ──────────────────────────────────────────────
        Atencion::observe(AtencionObserver::class);
        Servicio::observe(ServicioObserver::class);
        PagoOrdenCompra::observe(PagoOrdenCompraObserver::class);
        PagoProveedorCuenta::observe(PagoProveedorCuentaObserver::class);
        OrdenCompra::observe(OrdenCompraObserver::class);
        Cotizacion::observe(CotizacionObserver::class);
        Pago::observe(PagoObserver::class);
        PagoProveedor::observe(PagoProveedorObserver::class);

        // ── Auditoría y Logros (hooks de infraestructura) ─
        // Los eventos de dominio se gestionan en EventServiceProvider

        // AuditLogger: todos los modelos
        Event::listen('eloquent.updating: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;
            if ($model instanceof Model) {
                AuditLogger::captureBeforeUpdate($model);
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
            }
        });

        Event::listen('eloquent.deleted: *', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;
            if ($model instanceof Model) {
                AuditLogger::logModelDeleted($model);
            }
        });

        // LogroPersonalLogger: solo modelos trackeables (Atencion, Cotizacion, OrdenCompra)
        $trackeables = [
            \App\Models\Atencion::class,
            \App\Models\Cotizacion::class,
            \App\Models\OrdenCompra::class,
        ];

        foreach ($trackeables as $modelClass) {
            Event::listen("eloquent.updating: {$modelClass}", function ($model): void {
                if ($model instanceof Model) {
                    LogroPersonalLogger::captureBeforeUpdate($model);
                }
            });

            Event::listen("eloquent.updated: {$modelClass}", function ($model): void {
                if ($model instanceof Model) {
                    LogroPersonalLogger::logStatusChange($model);
                }
            });
        }
    }
}
