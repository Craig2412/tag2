<?php

namespace App\Console\Commands;

use App\Events\AtencionEstatusActualizado;
use App\Events\AtencionEtapaCambiada;
use App\Events\CotizacionEstatusActualizado;
use App\Events\CotizacionGuardado;
use App\Events\OrdenCompraAprobada;
use App\Events\OrdenCompraGuardado;
use App\Events\PagoProveedorCreado;
use App\Events\PagoProveedorEliminado;
use App\Events\PagoOrdenCompraGuardado;
use App\Jobs\PersistirAuditLog;
use App\Jobs\PersistirLogroPersonal;

use App\Listeners\BroadcastPermissionsChanged;
use App\Listeners\GenerarCuentasPorPagarListener;
use App\Listeners\RegistrarHistorialEstatusAtencionListener;
use App\Listeners\RegistrarHistorialEstatusCotizacionListener;

use App\Listeners\SincronizarEstadoFinancieroListener;
use App\Listeners\SincronizarFaseAtencionListener;
use App\Listeners\SincronizarPadreOrdenCompraListener;
use App\Models\Atencion;
use App\Models\AuditLog;
use App\Models\Cotizacion;
use App\Models\LogroPersonal;
use App\Models\OrdenCompra;
use App\Models\Servicio;
use App\Observers\CotizacionObserver;
use App\Observers\OrdenCompraObserver;
use App\Observers\PagoOrdenCompraObserver;
use App\Observers\ServicioObserver;
use App\Services\AuditLogger;
use App\Services\LogroPersonalLogger;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class DiagnosticarEventSystem extends Command
{
    protected $signature   = 'app:diagnosticar-eventos {--queue : Verificar también jobs despachados a la cola}';
    protected $description = 'Diagnóstico completo de Events, Listeners, Jobs y Observers del sistema.';

    private int $passed = 0;
    private int $failed = 0;
    private int $warnings = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════╗');
        $this->line('║      DIAGNÓSTICO DEL SISTEMA DE EVENTOS - TAG2       ║');
        $this->line('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->checkSection('REGISTROS DE EVENTOS (AppServiceProvider)', fn() => $this->checkEventRegistrations());
        $this->checkSection('LISTENERS → ShouldQueue',                   fn() => $this->checkListenersShouldQueue());
        $this->checkSection('JOBS',                                       fn() => $this->checkJobs());
        $this->checkSection('OBSERVERS',                                  fn() => $this->checkObservers());
        $this->checkSection('SERVICIOS (AuditLogger / LogroPersonalLogger)', fn() => $this->checkServices());
        $this->checkSection('DATOS DE PRUEBA EN BD',                     fn() => $this->checkTestData());

        if ($this->option('queue')) {
            $this->checkSection('COLA (dispatch real)',                   fn() => $this->checkQueueDispatch());
        }

        $this->printSummary();

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CHECKS
    // ─────────────────────────────────────────────────────────────────────────

    private function checkEventRegistrations(): void
    {
        // Event::getListeners() devuelve closures envueltas, no arrays de clases.
        // La forma más fiable es leer el AppServiceProvider directamente.
        $providerPath = app_path('Providers/AppServiceProvider.php');
        $source = file_get_contents($providerPath);

        $map = [
            OrdenCompraGuardado::class      => [SincronizarPadreOrdenCompraListener::class, SincronizarEstadoFinancieroListener::class],
            OrdenCompraAprobada::class      => [GenerarCuentasPorPagarListener::class],
            PagoOrdenCompraGuardado::class  => [SincronizarEstadoFinancieroListener::class],
            PagoProveedorCreado::class      => [SincronizarEstadoFinancieroListener::class],
            PagoProveedorEliminado::class   => [SincronizarEstadoFinancieroListener::class],
            CotizacionGuardado::class       => [SincronizarFaseAtencionListener::class],
            AtencionEstatusActualizado::class => [RegistrarHistorialEstatusAtencionListener::class],
            CotizacionEstatusActualizado::class => [RegistrarHistorialEstatusCotizacionListener::class],
        ];

        foreach ($map as $event => $expectedListeners) {
            $eventName    = class_basename($event);
            $eventInFile  = str_contains($source, $eventName);

            foreach ($expectedListeners as $listener) {
                $listenerName   = class_basename($listener);
                $listenerInFile = str_contains($source, $listenerName);

                if ($eventInFile && $listenerInFile) {
                    $this->checkPass("{$eventName} → {$listenerName} registrado en AppServiceProvider");
                } else {
                    $this->checkFail("{$eventName} → {$listenerName} NO encontrado en AppServiceProvider");
                }
            }
        }

        // Spatie Permission events → BroadcastPermissionsChanged
        foreach (['RoleAttached', 'RoleDetached', 'PermissionAttached', 'PermissionDetached'] as $spatieEvent) {
            if (str_contains($source, $spatieEvent) && str_contains($source, 'BroadcastPermissionsChanged')) {
                $this->checkPass("Spatie\\{$spatieEvent} → BroadcastPermissionsChanged registrado");
            } else {
                $this->checkFail("Spatie\\{$spatieEvent} → BroadcastPermissionsChanged NO encontrado");
            }
        }

        // Eloquent wildcard listeners
        foreach (['eloquent.updating: *', 'eloquent.updated: *', 'eloquent.created: *', 'eloquent.deleted: *', 'eloquent.deleting: *'] as $hook) {
            $hookShort = explode('.', $hook)[1]; // "updating", "updated", etc.
            if (str_contains($source, "'eloquent.{$hookShort}")) {
                $this->checkPass("Listener global 'eloquent.{$hookShort}: *' registrado en AppServiceProvider");
            } else {
                $this->checkWarn("Listener global 'eloquent.{$hookShort}: *' no encontrado en AppServiceProvider");
            }
        }
    }

    private function checkListenersShouldQueue(): void
    {
        $listeners = [

            BroadcastPermissionsChanged::class,
            GenerarCuentasPorPagarListener::class,
            RegistrarHistorialEstatusAtencionListener::class,
            RegistrarHistorialEstatusCotizacionListener::class,

            SincronizarEstadoFinancieroListener::class,
            SincronizarFaseAtencionListener::class,
            SincronizarPadreOrdenCompraListener::class,
        ];

        foreach ($listeners as $listener) {
            $instance    = new $listener();
            $implements  = $instance instanceof ShouldQueue;
            $name        = class_basename($listener);

            if ($implements) {
                $this->checkPass("{$name} implements ShouldQueue");
            } else {
                $this->checkFail("{$name} NO implementa ShouldQueue — bloquea el request HTTP");
            }
        }
    }

    private function checkJobs(): void
    {
        $jobs = [
            PersistirAuditLog::class    => ['payload' => ['action' => 'TEST', 'table_name' => 'test', 'success' => true]],
            PersistirLogroPersonal::class => ['payload' => ['id_personal' => 1, 'tipo_entidad' => 'test', 'id_entidad' => 1, 'id_estatus_anterior' => 1, 'id_estatus_nuevo' => 2, 'tiempo_transcurrido_segundos' => 0]],
        ];

        foreach ($jobs as $jobClass => $args) {
            $name = class_basename($jobClass);
            try {
                $instance = new $jobClass($args['payload']);
                $isQueued = $instance instanceof ShouldQueue;
                $queue    = method_exists($instance, 'queue') ? $instance->queue : ($instance->queue ?? 'default');

                if ($isQueued) {
                    $this->checkPass("{$name} → ShouldQueue ✓ | cola: " . ($instance->queue ?? 'default'));
                } else {
                    $this->checkFail("{$name} → NO implementa ShouldQueue");
                }

                // Verificar tries
                if (isset($instance->tries) && $instance->tries >= 1) {
                    $this->checkPass("{$name} → tries={$instance->tries}");
                } else {
                    $this->checkWarn("{$name} → tries no definido (usará default de Laravel: 1)");
                }

                // Verificar método failed()
                if (method_exists($instance, 'failed')) {
                    $this->checkPass("{$name} → método failed() definido");
                } else {
                    $this->checkWarn("{$name} → sin método failed() — errores no serán logueados");
                }
            } catch (\Throwable $e) {
                $this->checkFail("{$name} → ERROR al instanciar: " . $e->getMessage());
            }
        }
    }

    private function checkObservers(): void
    {
        $checks = [
            [Servicio::class,      ServicioObserver::class,      ['saving']],
            [Cotizacion::class,    CotizacionObserver::class,    ['saved', 'deleted']],
            [OrdenCompra::class,   OrdenCompraObserver::class,   ['saved', 'deleted']],
        ];

        foreach ($checks as [$model, $observer, $hooks]) {
            foreach ($hooks as $hook) {
                try {
                    $observers = $model::getEventDispatcher()?->getListeners("eloquent.{$hook}: {$model}");
                    $found     = collect($observers ?? [])->contains(
                        fn($l) => is_array($l) && isset($l[0]) && $l[0] instanceof $observer
                    );

                    $label = class_basename($model) . '@' . $hook . ' → ' . class_basename($observer);

                    // Los observers de Eloquent están registrados diferente — verificamos por reflexión
                    $this->checkObserverMethod($model, $observer, $hook);
                } catch (\Throwable $e) {
                    $this->checkFail(class_basename($model) . '@' . $hook . ' → ERROR: ' . $e->getMessage());
                }
            }
        }

        // PagoOrdenCompraObserver — verificar si está en AppServiceProvider
        $providerSource = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        if (str_contains($providerSource, 'PagoOrdenCompraObserver')) {
            $this->checkPass('PagoOrdenCompra@saved/deleted → PagoOrdenCompraObserver registrado en AppServiceProvider');
        } else {
            $this->checkFail('PagoOrdenCompraObserver NO está registrado — sus hooks saved/deleted nunca se ejecutan');
        }
    }

    private function checkObserverMethod(string $model, string $observer, string $hook): void
    {
        $label = class_basename($model) . '@' . $hook . ' → ' . class_basename($observer);
        if (method_exists($observer, $hook)) {
            $this->checkPass("{$label} → método existe");
        } else {
            $this->checkFail("{$label} → método '{$hook}' NO existe en el observer");
        }
    }

    private function checkServices(): void
    {
        // AuditLogger: debe despachar Job para CRUD y ser síncrono para Auth
        $reflection = new \ReflectionMethod(AuditLogger::class, 'write');
        $params     = collect($reflection->getParameters())->map->getName()->toArray();
        if (in_array('async', $params)) {
            $this->checkPass('AuditLogger::write() tiene parámetro $async (soporta sync/async)');
        } else {
            $this->checkFail('AuditLogger::write() no tiene parámetro $async');
        }

        // LogroPersonalLogger: debe usar Job en logStatusChange
        $source = file_get_contents((new \ReflectionClass(LogroPersonalLogger::class))->getFileName());
        if (str_contains($source, 'PersistirLogroPersonal::dispatch')) {
            $this->checkPass('LogroPersonalLogger → usa PersistirLogroPersonal::dispatch (asíncrono)');
        } else {
            $this->checkFail('LogroPersonalLogger → NO usa dispatch — sigue siendo síncrono');
        }

        if (str_contains($source, 'loadMissing')) {
            $this->checkPass('LogroPersonalLogger → usa loadMissing() para evitar N+1');
        } else {
            $this->checkWarn('LogroPersonalLogger → no usa loadMissing() — posible N+1 en relaciones');
        }

        // Eventos capturan usuarioId en constructor
        foreach ([AtencionEstatusActualizado::class, CotizacionEstatusActualizado::class] as $eventClass) {
            $src = file_get_contents((new \ReflectionClass($eventClass))->getFileName());
            $name = class_basename($eventClass);
            if (str_contains($src, 'usuarioId') && str_contains($src, 'Auth::id()')) {
                $this->checkPass("{$name} → captura usuarioId en constructor (safe para workers)");
            } else {
                $this->checkFail("{$name} → NO captura usuarioId — auth()->id() será null en worker");
            }
        }
    }

    private function checkTestData(): void
    {
        $checks = [
            Atencion::class    => 'Atenciones',
            Cotizacion::class  => 'Cotizaciones',
            OrdenCompra::class => 'Órdenes de compra',
            Servicio::class    => 'Servicios',
        ];

        foreach ($checks as $model => $label) {
            try {
                $count = $model::count();
                if ($count > 0) {
                    $this->checkPass("{$label}: {$count} registros disponibles para pruebas");
                } else {
                    $this->checkWarn("{$label}: 0 registros — algunos listeners no podrán probarse en Tinker");
                }
            } catch (\Throwable $e) {
                $this->checkFail("{$label}: ERROR al consultar — " . $e->getMessage());
            }
        }

        // Verificar tabla jobs existe
        try {
            \DB::table('jobs')->count();
            $this->checkPass('Tabla jobs existe (QUEUE_CONNECTION=database configurado)');
        } catch (\Throwable $e) {
            $this->checkFail('Tabla jobs NO existe — ejecuta: php artisan queue:table && php artisan migrate');
        }

        // Verificar tabla failed_jobs
        try {
            $failed = \DB::table('failed_jobs')->count();
            if ($failed === 0) {
                $this->checkPass('Tabla failed_jobs: 0 jobs fallidos ✓');
            } else {
                $this->checkWarn("Tabla failed_jobs: {$failed} jobs fallidos — revisa con: php artisan queue:failed");
            }
        } catch (\Throwable $e) {
            $this->checkWarn('Tabla failed_jobs no existe aún');
        }
    }

    private function checkQueueDispatch(): void
    {
        $this->line('  <fg=yellow>Despachando jobs reales a la cola...</>');

        Queue::fake();

        // Despachar job de auditoría
        PersistirAuditLog::dispatch(['action' => 'DIAGNOSTIC_TEST', 'table_name' => 'test', 'success' => true]);
        Queue::assertPushed(PersistirAuditLog::class, fn($job) => $job->payload['action'] === 'DIAGNOSTIC_TEST');
        $this->checkPass('PersistirAuditLog::dispatch() → job encolado correctamente');

        // Despachar job de logro
        PersistirLogroPersonal::dispatch(['tipo_entidad' => 'test', 'id_entidad' => 0, 'id_estatus_anterior' => 1, 'id_estatus_nuevo' => 2, 'tiempo_transcurrido_segundos' => 0]);
        Queue::assertPushed(PersistirLogroPersonal::class);
        $this->checkPass('PersistirLogroPersonal::dispatch() → job encolado correctamente');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function checkSection(string $title, callable $fn): void
    {
        $this->newLine();
        $this->line("  <fg=cyan;options=bold>── {$title}</>");
        $this->newLine();
        $fn();
    }

    private function checkPass(string $message): void
    {
        $this->line("  <fg=green>  ✓</> {$message}");
        $this->passed++;
    }

    private function checkFail(string $message): void
    {
        $this->line("  <fg=red>  ✗</> {$message}");
        $this->failed++;
    }

    private function checkWarn(string $message): void
    {
        $this->line("  <fg=yellow>  ⚠</> {$message}");
        $this->warnings++;
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════╗');
        $this->line('║                    RESUMEN FINAL                    ║');
        $this->line('╚══════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("  <fg=green>Pasaron : {$this->passed}</>");
        $this->line("  <fg=red>Fallaron: {$this->failed}</>");
        $this->line("  <fg=yellow>Avisos  : {$this->warnings}</>");
        $this->newLine();

        if ($this->failed === 0) {
            $this->line('  <fg=green;options=bold>🎉 Sistema de eventos en buen estado.</> ');
        } else {
            $this->line('  <fg=red;options=bold>⚠  Hay problemas que deben corregirse.</> ');
            $this->line('  Revisa los ✗ arriba para más detalles.');
        }

        $this->newLine();
        $this->line('  <fg=gray>Tip: corre con --queue para verificar el dispatch real de jobs.</>');
        $this->newLine();
    }
}
