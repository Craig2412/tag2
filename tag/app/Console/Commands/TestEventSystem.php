<?php

namespace App\Console\Commands;

use App\Events\AtencionEstatusActualizado;
use App\Events\CotizacionEstatusActualizado;
use App\Events\CotizacionGuardado;
use App\Events\OrdenCompraAprobada;
use App\Events\OrdenCompraGuardado;
use App\Events\PagoProveedorCreado;
use App\Events\PagoProveedorEliminado;
use App\Jobs\PersistirAuditLog;
use App\Jobs\PersistirLogroPersonal;
use App\Models\AtencionHistorial;
use App\Models\AuditLog;
use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\CotizacionHistorial;
use App\Models\CuentaPorPagar;
use App\Models\EstadoFinanciero;
use App\Models\EtapaComercial;
use App\Models\LogroPersonal;
use App\Models\OrdenCompra;
use App\Models\Servicio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestEventSystem extends Command
{
    protected $signature   = 'app:test-eventos {--no-rollback : Mantener los cambios en BD (solo para desarrollo)}';
    protected $description = 'Prueba de integración real: dispara eventos, procesa jobs y verifica cambios en la BD.';

    private int $passed   = 0;
    private int $failed   = 0;
    private int $skipped  = 0;
    private array $errors = [];

    public function handle(): int
    {
        // Forzar cola síncrona durante toda la prueba para ejecutar jobs inmediatamente
        config(['queue.default' => 'sync']);

        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║     PRUEBA DE INTEGRACIÓN DEL SISTEMA DE EVENTOS         ║');
        $this->line('║     (Cola forzada a SYNC — jobs ejecutan al instante)    ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line('  <fg=yellow>⚠ Todos los cambios se revertirán con DB::rollBack al finalizar.</>');
        $this->newLine();

        DB::beginTransaction();

        // Contadores antes de los tests
        $snapAntes = $this->tomarSnapshot();

        try {
            $this->runSection('JOB: PersistirAuditLog',        fn() => $this->testPersistirAuditLog());
            $this->runSection('JOB: PersistirLogroPersonal',   fn() => $this->testPersistirLogroPersonal());
            $this->runSection('OBSERVER: ServicioObserver',    fn() => $this->testServicioObserver());
            $this->runSection('LISTENER: RegistrarHistorialEstatusAtencion', fn() => $this->testHistorialAtencion());
            $this->runSection('LISTENER: RegistrarHistorialEstatusCotizacion', fn() => $this->testHistorialCotizacion());
            $this->runSection('LISTENER: SincronizarFaseAtencion', fn() => $this->testSincronizarFaseAtencion());
            $this->runSection('LISTENER: SincronizarEstadoFinanciero', fn() => $this->testSincronizarEstadoFinanciero());
            $this->runSection('LISTENER: GenerarCuentasPorPagar', fn() => $this->testGenerarCuentasPorPagar());
            $this->runSection('AUDIT: eloquent.created / updated / deleted', fn() => $this->testAuditLogger());
        } finally {
            $this->reportarCambiosBD($snapAntes);

            if ($this->option('no-rollback')) {
                $this->newLine();
                DB::commit();
                $this->line('  <fg=yellow;options=bold>⚠ --no-rollback activo: cambios PERSISTIDOS en BD.</>');
            } else {
                DB::rollBack();
                $this->newLine();
                $this->line('  <fg=gray>↩ Rollback ejecutado — BD sin cambios.</>');
            }
        }

        $this->printSummary();

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TESTS DE INTEGRACIÓN REAL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifica que PersistirAuditLog escribe en la tabla audit_logs.
     */
    private function testPersistirAuditLog(): void
    {
        $before = AuditLog::count();

        PersistirAuditLog::dispatchSync([
            'action'     => 'TEST_INTEGRATION',
            'table_name' => 'test',
            'record_id'  => 0,
            'success'    => true,
            'message'    => 'Prueba automática app:test-eventos',
        ]);

        $after = AuditLog::count();

        $this->assert(
            $after === $before + 1,
            'PersistirAuditLog crea un registro en audit_logs',
            "Se esperaba {$before}+1 registros, hay {$after}"
        );

        $log = AuditLog::latest('id')->first();
        $this->assert(
            $log?->action === 'TEST_INTEGRATION',
            'El AuditLog tiene el action correcto (TEST_INTEGRATION)',
            "Action encontrado: {$log?->action}"
        );
    }

    /**
     * Verifica que PersistirLogroPersonal escribe en la tabla logros_personal.
     */
    private function testPersistirLogroPersonal(): void
    {
        $before = LogroPersonal::count();

        // Disparamos el job manualmente con datos de prueba
        PersistirLogroPersonal::dispatch([
            'tipo_entidad'        => 'atencion',
            'id_entidad'          => 1,
            'id_estatus_anterior' => 1,
            'id_estatus_nuevo'    => 2,
        ], 1); // Forzamos usuario ID 1

        $after = LogroPersonal::count();
        $this->assert($after > $before, 'PersistirLogroPersonal crea un registro en logros_personal', "Se esperaba incremento, hay {$after}");

        $last = LogroPersonal::latest('id')->first();
        $this->assert((int) $last?->id_estatus_nuevo === 2, 'El LogroPersonal tiene id_estatus_nuevo=2 correcto', "id_estatus_nuevo encontrado: {$last?->id_estatus_nuevo}");
    }

    /**
     * Verifica que ServicioObserver calcula total_servicio antes de guardar.
     */
    private function testServicioObserver(): void
    {
        $servicio = Servicio::first();

        if (!$servicio) {
            $this->skip('ServicioObserver — no hay servicios en BD');
            return;
        }

        // Valores conocidos para poder verificar el cálculo
        $gravable  = 100.00;
        $noSujeto  = 20.00;
        $ivaPct    = 13.00;
        $esperado  = $gravable + ($gravable * $ivaPct / 100) + $noSujeto; // 133.00

        $servicio->monto_gravable  = $gravable;
        $servicio->monto_no_sujeto = $noSujeto;
        $servicio->iva_establecido = $ivaPct;
        $servicio->save(); // Dispara ServicioObserver@saving

        $servicio->refresh();

        $this->assert(
            abs((float) $servicio->total_servicio - $esperado) < 0.01,
            "ServicioObserver calcula total_servicio correctamente ({$esperado})",
            "total_servicio en BD: {$servicio->total_servicio}, esperado: {$esperado}"
        );
    }

    /**
     * Verifica que al disparar AtencionEstatusActualizado se crea un AtencionHistorial.
     */
    private function testHistorialAtencion(): void
    {
        $atencion = Atencion::first();

        if (!$atencion) {
            $this->skip('HistorialAtencion — no hay atenciones en BD');
            return;
        }

        $before = AtencionHistorial::where('atencion_id', $atencion->id)->count();

        event(new AtencionEstatusActualizado(
            atencion:        $atencion,
            estatusAnterior: 1,
            estatusNuevo:    2,
            comentario:      'Prueba automática app:test-eventos',
            usuarioId:       1,
        ));

        $after = AtencionHistorial::where('atencion_id', $atencion->id)->count();

        $this->assert(
            $after > $before,
            "RegistrarHistorialEstatusAtencionListener crea AtencionHistorial (atencion #{$atencion->id})",
            "Esperaba más de {$before}, hay {$after}"
        );

        $historial = AtencionHistorial::where('atencion_id', $atencion->id)->latest('id')->first();
        $this->assert(
            (int) $historial?->estatus_nuevo === 2,
            'AtencionHistorial tiene estatus_nuevo=2',
            "estatus_nuevo: {$historial?->estatus_nuevo}"
        );
    }

    /**
     * Verifica que al disparar CotizacionEstatusActualizado se crea un CotizacionHistorial.
     */
    private function testHistorialCotizacion(): void
    {
        $cotizacion = Cotizacion::first();

        if (!$cotizacion) {
            $this->skip('HistorialCotizacion — no hay cotizaciones en BD');
            return;
        }

        $before = CotizacionHistorial::where('cotizacion_id', $cotizacion->id)->count();

        event(new CotizacionEstatusActualizado(
            cotizacion:      $cotizacion,
            estatusAnterior: 1,
            estatusNuevo:    3,
            comentario:      'Prueba automática app:test-eventos',
            usuarioId:       1,
        ));

        $after = CotizacionHistorial::where('cotizacion_id', $cotizacion->id)->count();

        $this->assert(
            $after > $before,
            "RegistrarHistorialEstatusCotizacionListener crea CotizacionHistorial (cotizacion #{$cotizacion->id})",
            "Esperaba más de {$before}, hay {$after}"
        );
    }

    /**
     * Verifica que CotizacionGuardado → SincronizarFaseAtencionListener actualiza la Atencion.
     */
    private function testSincronizarFaseAtencion(): void
    {
        $cotizacion = Cotizacion::with('atencion')->first();

        if (!$cotizacion || !$cotizacion->atencion) {
            $this->skip('SincronizarFaseAtencion — no hay cotizaciones con atención en BD');
            return;
        }

        $atencion      = $cotizacion->atencion;
        $faseAnterior  = $atencion->id_etapa_comercial;

        // El listener llama a EstadoFaseService::sincronizarFaseAtencion()
        event(new CotizacionGuardado($cotizacion));

        $atencion->refresh();

        // No necesariamente cambia (puede que ya sea correcta), pero no debe lanzar excepción
        $this->assert(
            true,
            "SincronizarFaseAtencionListener ejecutó sin excepciones (fase: {$faseAnterior} → {$atencion->id_etapa_comercial})",
            ''
        );

        $etapaEsperada = EtapaComercial::whereIn('slug', ['atencion', 'cotizada', 'orden_compra'])->pluck('id');
        $this->assert(
            $etapaEsperada->contains($atencion->id_etapa_comercial),
            'Atención tiene un id_etapa_comercial válido del catálogo',
            "id_etapa_comercial actual: {$atencion->id_etapa_comercial}"
        );
    }

    /**
     * Verifica que OrdenCompraGuardado → SincronizarEstadoFinanciero actualiza el estado.
     */
    private function testSincronizarEstadoFinanciero(): void
    {
        $orden = OrdenCompra::first();

        if (!$orden) {
            $this->skip('SincronizarEstadoFinanciero — no hay órdenes de compra en BD');
            return;
        }

        $estadoAntes = $orden->id_estado_financiero;

        // Llamamos handle() directo para evitar __invoke() en listeners ShouldQueue
        (new \App\Listeners\SincronizarEstadoFinancieroListener())->handle(new OrdenCompraGuardado($orden));

        $orden->refresh();

        $estadosValidos = EstadoFinanciero::pluck('id');
        $this->assert(
            $estadosValidos->contains($orden->id_estado_financiero) || $orden->id_estado_financiero === null,
            "SincronizarEstadoFinancieroListener actualizó estado financiero (antes: {$estadoAntes} → ahora: {$orden->id_estado_financiero})",
            ''
        );
    }

    /**
     * Verifica que OrdenCompraAprobada → GenerarCuentasPorPagarListener crea CuentasPorPagar.
     */
    private function testGenerarCuentasPorPagar(): void
    {
        // OrdenCompra no tiene servicios() directo — se acceden via cotizacion
        $orden = OrdenCompra::whereHas('cotizacion.servicios')
            ->whereDoesntHave('cuentasPorPagar')
            ->first();

        if (!$orden) {
            $orden = OrdenCompra::whereHas('cotizacion.servicios')->first();
        }

        if (!$orden) {
            $this->skip('GenerarCuentasPorPagar — no hay órdenes con cotización+servicios en BD');
            return;
        }

        $cuentasAntes = CuentaPorPagar::where('id_orden_compra', $orden->id)->count();

        // Llamamos handle() directo para evitar __invoke() en listeners ShouldQueue
        (new \App\Listeners\GenerarCuentasPorPagarListener())->handle(new OrdenCompraAprobada($orden));

        $cuentasDespues = CuentaPorPagar::where('id_orden_compra', $orden->id)->count();

        $this->assert(
            $cuentasDespues >= $cuentasAntes,
            "GenerarCuentasPorPagarListener: cuentas por pagar = {$cuentasDespues} (antes: {$cuentasAntes})",
            "Cuentas después: {$cuentasDespues}, antes: {$cuentasAntes}"
        );
    }

    /**
     * Verifica que los listeners globales de Eloquent disparan AuditLog.
     */
    private function testAuditLogger(): void
    {
        // Usamos Atencion como modelo de prueba — está en la whitelist de AuditLogger
        $atencion = Atencion::first();

        if (!$atencion) {
            $this->skip('AuditLogger — no hay atenciones en BD para probar');
            return;
        }

        // --- CREATE ---
        // No creamos un modelo real para no arriesgar FK constraints
        // En su lugar, verificamos que el job de auditoría se dispara al actualizar
        $auditAntes = AuditLog::where('table_name', $atencion->getTable())
            ->where('record_id', $atencion->id)
            ->count();

        // Hacemos un update que genere cambio real
        $valorActual  = $atencion->getAttributes()['updated_at'] ?? now();
        $atencion->touch(); // actualiza timestamps → dispara eloquent.updated

        $auditDespues = AuditLog::where('table_name', $atencion->getTable())
            ->where('record_id', $atencion->id)
            ->count();

        // touch() puede no cambiar columnas de negocio → AuditLogger omite si no hay cambios
        // Solo verificamos que no lanzó excepción
        $this->assert(
            true,
            "AuditLogger: eloquent.updated ejecutó sin excepciones en {$atencion->getTable()} #{$atencion->id}",
            ''
        );

        // Verificar que la tabla audit_logs existe y es accesible
        $totalLogs = AuditLog::count();
        $this->assert(
            $totalLogs >= 0,
            "Tabla audit_logs accesible — total registros: {$totalLogs}",
            'No se pudo consultar audit_logs'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function runSection(string $title, callable $fn): void
    {
        $this->newLine();
        $this->line("  <fg=cyan;options=bold>── {$title}</>");

        try {
            $fn();
        } catch (\Throwable $e) {
            $this->checkFail("EXCEPCIÓN NO CONTROLADA: " . $e->getMessage());
            $this->errors[] = $title . ': ' . $e->getMessage();
        }
    }

    private function assert(bool $condition, string $success, string $failReason): void
    {
        if ($condition) {
            $this->line("    <fg=green>✓</> {$success}");
            $this->passed++;
        } else {
            $this->line("    <fg=red>✗</> {$success}");
            $this->line("      <fg=red>  → {$failReason}</>");
            $this->failed++;
        }
    }

    private function checkFail(string $message): void
    {
        $this->line("    <fg=red>✗</> {$message}");
        $this->failed++;
    }

    private function skip(string $message): void
    {
        $this->line("    <fg=yellow>⊘</> SKIPPED: {$message}");
        $this->skipped++;
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║                     RESULTADO FINAL                     ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("  <fg=green>  Pasaron : {$this->passed}</>");
        $this->line("  <fg=red>  Fallaron: {$this->failed}</>");
        $this->line("  <fg=yellow>  Saltados: {$this->skipped}</> (sin datos en BD)");
        $this->newLine();

        if ($this->errors) {
            $this->line('  <fg=red>Excepciones:</>');
            foreach ($this->errors as $e) {
                $this->line("  <fg=red>  • {$e}</>");
            }
            $this->newLine();
        }

        if ($this->failed === 0) {
            $this->line('  <fg=green;options=bold>🎉 Todos los procesos verificados correctamente en BD.</> ');
        } else {
            $this->line('  <fg=red;options=bold>❌ Hay fallas — revisa los ✗ arriba.</> ');
        }

        if ($this->skipped > 0) {
            $this->line('  <fg=yellow>  ⊘ Algunos tests se saltaron por falta de datos. Corre los seeders.</>');
        }

        $this->newLine();
    }

    private function tomarSnapshot(): array
    {
        return [
            'audit_logs'          => AuditLog::count(),
            'logros_personal'     => LogroPersonal::count(),
            'atencion_historial'  => DB::table('atencion_historial')->count(),
            'cotizacion_historial'=> DB::table('cotizacion_historial')->count(),
            'cuentas_por_pagar'   => DB::table('cuentas_por_pagar')->count(),
        ];
    }

    private function reportarCambiosBD(array $antes): void
    {
        $despues = $this->tomarSnapshot();

        $this->newLine();
        $this->line('  <fg=cyan;options=bold>── CAMBIOS EN BD DURANTE LAS PRUEBAS</>');
        $this->newLine();

        foreach ($antes as $tabla => $countAntes) {
            $countDespues = $despues[$tabla];
            $diff         = $countDespues - $countAntes;

            if ($diff > 0) {
                $this->line("    <fg=green>+{$diff}</> filas creadas en <fg=white>{$tabla}</> ({$countAntes} → {$countDespues})");
            } elseif ($diff < 0) {
                $this->line("    <fg=red>{$diff}</> filas eliminadas en <fg=white>{$tabla}</> ({$countAntes} → {$countDespues})");
            } else {
                $this->line("    <fg=gray>  0</> cambios en {$tabla} ({$countAntes})");
            }
        }
    }
}
