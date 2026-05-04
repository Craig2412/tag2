<?php

namespace App\Providers;

use App\Services\AuditLogger;
use App\Services\LogroPersonalLogger;
use App\Models\Servicio;
use App\Models\PagoOrdenCompra;
use App\Models\Cotizacion;
use App\Models\OrdenCompra;
use App\Observers\ServicioObserver;

use App\Events\OrdenCompraGuardado;
use App\Events\OrdenCompraAprobada;
use App\Events\PagoOrdenCompraGuardado;
use App\Events\PagoProveedorCreado;
use App\Events\PagoProveedorEliminado;
use App\Events\CotizacionGuardado;
use App\Events\AtencionEstatusActualizado;
use App\Events\CotizacionEstatusActualizado;

use App\Listeners\SincronizarPadreOrdenCompraListener;
use App\Listeners\SincronizarEstadoFinancieroListener;
use App\Listeners\SincronizarFaseAtencionListener;
use App\Listeners\GenerarCuentasPorPagarListener;
use App\Listeners\AmortizarCuentaPorPagarListener;
use App\Listeners\RestaurarSaldoCuentaPorPagarListener;
use App\Listeners\RegistrarHistorialEstatusAtencionListener;
use App\Listeners\RegistrarHistorialEstatusCotizacionListener;
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

        Event::listen(OrdenCompraGuardado::class, [
            SincronizarPadreOrdenCompraListener::class,
            SincronizarEstadoFinancieroListener::class,
        ]);

        Event::listen(OrdenCompraAprobada::class, GenerarCuentasPorPagarListener::class);
        
        // Pagos de Clientes (Ingresos)
        Event::listen(PagoOrdenCompraGuardado::class, SincronizarEstadoFinancieroListener::class);
        
        // Pagos a Proveedores (Egresos)
        Event::listen(PagoProveedorCreado::class, [
            AmortizarCuentaPorPagarListener::class,
            SincronizarEstadoFinancieroListener::class,
        ]);
        Event::listen(PagoProveedorEliminado::class, [
            RestaurarSaldoCuentaPorPagarListener::class,
            SincronizarEstadoFinancieroListener::class,
        ]);

        Event::listen(CotizacionGuardado::class, SincronizarFaseAtencionListener::class);

        // Historial de cambios de estatus operativo
        Event::listen(AtencionEstatusActualizado::class, RegistrarHistorialEstatusAtencionListener::class);
        Event::listen(CotizacionEstatusActualizado::class, RegistrarHistorialEstatusCotizacionListener::class);

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
