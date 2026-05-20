<?php

namespace App\Providers;

use App\Events\AtencionEstatusActualizado;
use App\Events\AtencionEtapaCambiada;
use App\Events\CotizacionEstatusActualizado;
use App\Events\CotizacionGuardado;
use App\Events\OrdenCompraAprobada;
use App\Events\OrdenCompraGuardado;
use App\Events\PagoOrdenCompraGuardado;
use App\Listeners\BroadcastPermissionsChanged;
use App\Listeners\GenerarCuentasPorPagarListener;
use App\Listeners\GenerarOrdenDesdeCotizacionListener;
use App\Listeners\RegistrarHistorialAtencionListener;
use App\Listeners\RegistrarHistorialEstatusAtencionListener;
use App\Listeners\RegistrarHistorialEstatusCotizacionListener;
use App\Listeners\SincronizarEstadoFinancieroListener;
use App\Listeners\SincronizarFaseAtencionListener;
use App\Listeners\SincronizarPadreOrdenCompraListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ── Ciclo de Cotización ──────────────────────────────────
        CotizacionGuardado::class => [
            SincronizarFaseAtencionListener::class,
        ],
        CotizacionEstatusActualizado::class => [
            RegistrarHistorialEstatusCotizacionListener::class,
            GenerarOrdenDesdeCotizacionListener::class,
        ],

        // ── Ciclo de Atención ────────────────────────────────────
        AtencionEstatusActualizado::class => [
            RegistrarHistorialEstatusAtencionListener::class,
        ],
        AtencionEtapaCambiada::class => [
            RegistrarHistorialAtencionListener::class,
        ],

        // ── Ciclo de Orden de Compra ─────────────────────────────
        OrdenCompraGuardado::class => [
            SincronizarPadreOrdenCompraListener::class,
            SincronizarEstadoFinancieroListener::class,
        ],
        OrdenCompraAprobada::class => [
            GenerarCuentasPorPagarListener::class,
        ],

        // ── Pagos de Clientes (Ingresos) ─────────────────────────
        PagoOrdenCompraGuardado::class => [
            SincronizarEstadoFinancieroListener::class,
        ],

        // ── Spatie Permissions → Broadcasting ────────────────────
        RoleAttached::class => [
            BroadcastPermissionsChanged::class,
        ],
        RoleDetached::class => [
            BroadcastPermissionsChanged::class,
        ],
        PermissionAttached::class => [
            BroadcastPermissionsChanged::class,
        ],
        PermissionDetached::class => [
            BroadcastPermissionsChanged::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
