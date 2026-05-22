<?php

namespace Tests\Feature;

use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\CuentaPorPagar;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommercialCycleTest extends TestCase
{
    use DatabaseTransactions;

    private int $idCliente;

    private int $idPersonal;

    private int $idAdmin;

    private int $idProveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = \App\Models\Usuario::firstOrCreate(
            ['correo' => 'admin@example.com'],
            ['nombre_usuario' => 'Admin Test', 'clave' => bcrypt('test')]
        );
        $this->idAdmin = $usuario->id;

        $cliente = \App\Models\Cliente::firstOrCreate(
            ['cedula' => 'V-99999999'],
            ['nombre' => 'Cliente Test', 'apellido' => 'Ciclo', 'telefono' => '+58 412 0000000', 'id_tipo_contribuyente' => 1]
        );
        $this->idCliente = $cliente->id;

        $personal = \App\Models\Personal::firstOrCreate(
            ['usuario_id' => $this->idAdmin],
            ['nombre' => 'Personal Test', 'apellido' => 'Ciclo', 'cedula' => 'V-11111111', 'telefono' => '+58 412 1111111', 'correo_institucional' => 'p@tag.com', 'porcentaje_comision' => 5.00]
        );
        $this->idPersonal = $personal->id;

        $proveedor = \App\Models\Proveedor::firstOrCreate(
            ['rif' => 'T-00000000-0'],
            ['nombre_empresa' => 'Proveedor Test', 'razon_comercial' => 'ProvTest', 'correo_empresa' => 'p@test.com', 'telefono_empresa' => '02120000000', 'nombre_persona_contacto' => 'Contacto', 'id_tipo_contribuyente' => 1, 'tipo_proveedor' => 1]
        );
        $this->idProveedor = $proveedor->id;
    }

    /**
     * Flujo completo feliz:
     * Atención → Cotización → Aprobación → OC → CxP → Pago cliente → Pago proveedor
     */
    public function test_flujo_completo_feliz(): void
    {
        // ── 1. Crear Atención ────────────────────────────────────
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Ciclo Completo',
            'id_estado_atencion' => 1, // abierta
            'id_etapa_comercial' => 1, // atencion
        ]);

        $this->assertEquals(1, $atencion->id_etapa_comercial);

        // ── 2. Crear Cotización ──────────────────────────────────
        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1, // pendiente
            'cant_adultos' => 2,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        // La creación debió disparar CotizacionGuardado → sincronizar fase
        $atencion->refresh();
        $this->assertEquals(2, $atencion->id_etapa_comercial, 'Etapa debe ser cotizada');

        // ── 3. Agregar servicios a la cotización ─────────────────
        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => $this->idProveedor,
            'costo' => 500.00,
            'monto_gravable' => 600.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 600.00,
            'id_tasa_cambio' => 1,
        ]);

        // ── 4. Aprobar Cotización (simula lo que hace el Controller) ─
        $cotizacion->update(['id_estado_cotizacion' => 2]); // aprobada

        // Disparar evento manualmente (como lo hace CotizacionController)
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        // ── 5. Verificar que se generó la OC ─────────────────────
        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $this->assertNotNull($orden, 'La Orden de Compra debió generarse automáticamente');
        $this->assertEquals(600.00, (float) $orden->monto_total);

        // ── 6. Verificar CxP generada ────────────────────────────
        $cxp = CuentaPorPagar::where('id_orden_compra', $orden->id)->first();
        $this->assertNotNull($cxp, 'La Cuenta por Pagar debió generarse');
        $this->assertEquals(500.00, (float) $cxp->monto_total);

        // ── 7. Verificar que la atención está cerrada ganada ─────
        $atencion->refresh();
        $this->assertEquals(3, $atencion->id_etapa_comercial, 'Etapa debe ser orden_compra');
        $this->assertEquals(2, $atencion->id_estado_atencion, 'Atención debe estar cerrada_ganada');

        // ── 8. Registrar Pago de Cliente ─────────────────────────
        $pago = Pago::create([
            'id_usuario' => $this->idAdmin,
            'fecha_pago' => now(),
            'monto_total' => 600.00,
            'id_metodo_pago' => 1,
            'nro_comprobante' => 'COMP-001',
            'id_tasa_cambio' => 1,
            'id_estado_conciliacion' => 1,
        ]);

        PagoOrdenCompra::create([
            'id_pago' => $pago->id,
            'id_orden_compra' => $orden->id,
            'monto_asignado' => 600.00,
        ]);

        // ── 9. Verificar estado financiero INGRESO = pagado ──────
        $orden->refresh();
        $this->assertEquals(3, $orden->id_estado_financiero, 'Ingreso debe estar pagado');

        // ── 10. Registrar Pago a Proveedor ───────────────────────
        $pagoProv = PagoProveedor::create([
            'id_proveedor' => $this->idProveedor,
            'id_metodo_pago' => 1,
            'monto_total' => 500.00,
            'fecha_pago' => now(),
            'referencia' => 'PAGO-PROV-001',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pagoProv->id,
            'id_cuenta_por_pagar' => $cxp->id,
            'monto_asignado' => 500.00,
        ]);

        // ── 11. Verificar estados finales ────────────────────────
        $orden->refresh();
        $cxp->refresh();

        $this->assertEquals(3, $orden->id_estado_financiero, 'Ingreso debe ser pagado');
        $this->assertEquals(3, $orden->id_estado_financiero_egreso, 'Egreso debe ser pagado');
        $this->assertEquals(3, $orden->id_estado_orden_compra, 'OC debe estar completada');
        $this->assertEquals(0.0, (float) $cxp->saldo_pendiente, 'Saldo pendiente CxP debe ser 0');
    }

    /**
     * Soft-delete de PagoProveedor debe revertir saldos de CxP.
     */
    public function test_soft_delete_pago_proveedor_revierte_saldos(): void
    {
        // ── Crear ciclo básico hasta pago proveedor ──────────────
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Soft Delete',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 2, // aprobada directo
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => $this->idProveedor,
            'costo' => 300.00,
            'monto_gravable' => 350.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 350.00,
            'id_tasa_cambio' => 1,
        ]);

        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $this->assertNotNull($orden);

        $cxp = CuentaPorPagar::where('id_orden_compra', $orden->id)->first();
        $this->assertNotNull($cxp);

        // Crear pago proveedor y asociarlo
        $pagoProv = PagoProveedor::create([
            'id_proveedor' => $this->idProveedor,
            'id_metodo_pago' => 1,
            'monto_total' => 200.00,
            'fecha_pago' => now(),
            'referencia' => 'PAGO-PARCIAL',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pagoProv->id,
            'id_cuenta_por_pagar' => $cxp->id,
            'monto_asignado' => 200.00,
        ]);

        $cxp->refresh();
        $saldoAntes = (float) $cxp->saldo_pendiente;

        // ── Soft-delete del pago ─────────────────────────────────
        $pagoProv->delete();

        $cxp->refresh();
        $orden->refresh();

        // El saldo debe haberse revertido
        $this->assertGreaterThan($saldoAntes, (float) $cxp->saldo_pendiente, 'Saldo debe aumentar tras revertir pago');
        $this->assertNotNull($pagoProv->deleted_at, 'Pago debe tener soft-delete');
    }

    /**
     * Rechazar todas las cotizaciones debe cerrar la atención como perdida.
     */
    public function test_rechazo_total_cierra_atencion_perdida(): void
    {
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Rechazo',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        // Crear 2 cotizaciones
        $cot1 = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        $cot2 = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1,
            'cant_adultos' => 2,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        // Rechazar ambas (simulando lo que hace CotizacionController)
        $cot1->update(['id_estado_cotizacion' => 3]); // rechazada
        event(new \App\Events\CotizacionEstatusActualizado($cot1, 1, 3));

        $cot2->update(['id_estado_cotizacion' => 3]); // rechazada
        event(new \App\Events\CotizacionEstatusActualizado($cot2, 1, 3));

        $atencion->refresh();

        // Debe estar cerrada_perdida porque todas las cotizaciones fueron rechazadas
        $this->assertEquals(3, $atencion->id_estado_atencion, 'Atención debe estar cerrada_perdida');
    }

    // ─────────────────────────────────────────────────────────────
    //  ANULACIÓN POR RECHAZO DE COTIZACIÓN (nuevo flujo)
    // ─────────────────────────────────────────────────────────────

    /**
     * Al rechazar una cotización que tiene OC aprobada, la OC debe
     * transicionar a estado "anulada" (NO soft-delete).
     */
    public function test_rechazar_cotizacion_anula_orden_compra(): void
    {
        // ── Crear ciclo completo hasta OC + CxP ──────────────────
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Anulación por Rechazo',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 2, // aprobada
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => $this->idProveedor,
            'costo' => 500.00,
            'monto_gravable' => 500.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 500.00,
            'id_tasa_cambio' => 1,
        ]);

        // Disparar aprobación → genera OC + CxP
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $this->assertNotNull($orden, 'OC debió generarse');
        $this->assertNotNull($orden->cuentasPorPagar()->first(), 'CxP debió generarse');

        $idAnulada = EstadoOrdenCompra::where('slug', 'anulada')->value('id');
        $this->assertNotNull($idAnulada, 'El estado "anulada" debe existir en el catálogo');

        // ── Rechazar cotización → debe anular OC ─────────────────
        $cotizacion->update(['id_estado_cotizacion' => 3]); // rechazada
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 2, 3));

        $orden->refresh();

        // La OC NO debe tener soft-delete
        $this->assertNull($orden->deleted_at, 'OC NO debe tener soft-delete');

        // La OC debe estar en estado "anulada"
        $this->assertEquals($idAnulada, (int) $orden->id_estado_orden_compra, 'OC debe estar anulada');
    }

    /**
     * Al rechazar una cotización con OC, la atención debe transicionar
     * a "cerrada_perdida" (porque la OC anulada no cuenta como válida).
     */
    public function test_rechazar_cotizacion_con_oc_cierra_atencion_perdida(): void
    {
        // ── Crear ciclo completo hasta OC + CxP ──────────────────
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Cierre Perdida por Rechazo con OC',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 2, // aprobada
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => $this->idProveedor,
            'costo' => 500.00,
            'monto_gravable' => 500.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 500.00,
            'id_tasa_cambio' => 1,
        ]);

        // Aprobar → genera OC + CxP
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $this->assertNotNull($orden, 'OC debió generarse');

        // La atención debió pasar a cerrada_ganada al aprobar
        $atencion->refresh();
        $this->assertEquals(2, (int) $atencion->id_estado_atencion, 'Atención debe estar cerrada_ganada tras aprobar');

        // ── Rechazar cotización → debe cerrar como perdida ───────
        $cotizacion->update(['id_estado_cotizacion' => 3]); // rechazada
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 2, 3));

        $atencion->refresh();
        $orden->refresh();

        // OC debe estar anulada
        $idAnulada = EstadoOrdenCompra::where('slug', 'anulada')->value('id');
        $this->assertEquals($idAnulada, (int) $orden->id_estado_orden_compra, 'OC debe estar anulada');

        // Atención debe estar cerrada_perdida (la OC anulada no cuenta como válida)
        $this->assertEquals(3, (int) $atencion->id_estado_atencion, 'Atención debe estar cerrada_perdida');
    }

    /**
     * Al rechazar una cotización con OC, las CxP deben ser soft-delete
     * y sus pivotes de pago a proveedor eliminados (hard delete).
     */
    public function test_rechazar_cotizacion_limpia_cuentas_por_pagar(): void
    {
        // ── Crear ciclo completo hasta OC + CxP con pago parcial ─
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Limpieza CxP por Rechazo',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 2,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => $this->idProveedor,
            'costo' => 800.00,
            'monto_gravable' => 800.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 800.00,
            'id_tasa_cambio' => 1,
        ]);

        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $cxp = CuentaPorPagar::where('id_orden_compra', $orden->id)->first();
        $this->assertNotNull($cxp, 'CxP debió generarse');

        // Crear pago parcial a proveedor
        $pagoProv = PagoProveedor::create([
            'id_proveedor' => $this->idProveedor,
            'id_metodo_pago' => 1,
            'monto_total' => 300.00,
            'fecha_pago' => now(),
            'referencia' => 'RECHAZO-001',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pagoProv->id,
            'id_cuenta_por_pagar' => $cxp->id,
            'monto_asignado' => 300.00,
        ]);

        $cxp->refresh();
        $this->assertLessThan(800.00, (float) $cxp->saldo_pendiente, 'Saldo debe reflejar pago parcial');

        // ── Rechazar cotización ──────────────────────────────────
        $cotizacion->update(['id_estado_cotizacion' => 3]);
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 2, 3));

        // ── Verificar CxP soft-delete ────────────────────────────
        $cxpTrashed = CuentaPorPagar::withTrashed()->find($cxp->id);
        $this->assertNotNull($cxpTrashed->deleted_at, 'CxP debe tener soft-delete');

        // ── Verificar pivote eliminado (hard delete) ─────────────
        $pivoteExiste = PagoProveedorCuenta::where('id_cuenta_por_pagar', $cxp->id)->exists();
        $this->assertFalse($pivoteExiste, 'Pivote de pago proveedor debe ser eliminado');

        // ── Verificar que el saldo fue revertido antes del soft-delete ──
        $this->assertEquals(800.00, (float) $cxpTrashed->saldo_pendiente, 'Saldo debe revertirse al monto total');
    }

    /**
     * Rechazar una cotización sin OC no debe lanzar errores.
     */
    public function test_rechazar_cotizacion_sin_oc_no_falla(): void
    {
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Rechazo Sin OC',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1, // pendiente (nunca aprobada, sin OC)
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        // No debe lanzar excepción
        $cotizacion->update(['id_estado_cotizacion' => 3]);
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 3));

        $cotizacion->refresh();
        $this->assertEquals(3, (int) $cotizacion->id_estado_cotizacion, 'Cotización debe estar rechazada');

        // No debe existir OC
        $this->assertNull(OrdenCompra::where('id_cotizacion', $cotizacion->id)->first());
    }

    /**
     * sincronizarCuentasPorPagar() no debe recrear CxP en una OC anulada.
     */
    public function test_sincronizar_cxp_no_recrea_en_oc_anulada(): void
    {
        // ── Crear ciclo completo y luego anular ──────────────────
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Guardia Sincronizar',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 2,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        Servicio::create([
            'id_cotizacion' => $cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => $this->idProveedor,
            'costo' => 200.00,
            'monto_gravable' => 200.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 200.00,
            'id_tasa_cambio' => 1,
        ]);

        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $this->assertNotNull($orden, 'OC debió generarse');

        // ── Anular la OC ─────────────────────────────────────────
        $cotizacion->update(['id_estado_cotizacion' => 3]);
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 2, 3));

        $orden->refresh();
        $this->assertNotNull($orden->estadoOrdenCompra);
        $this->assertEquals('anulada', $orden->estadoOrdenCompra->slug, 'OC debe estar anulada');

        // Contar CxP (soft-delete no cuenta en consultas normales)
        $cxpActivas = CuentaPorPagar::where('id_orden_compra', $orden->id)->count();
        $this->assertEquals(0, $cxpActivas, 'No deben existir CxP activas');

        // ── Llamar a sincronizarCuentasPorPagar ──────────────────
        $orden->sincronizarCuentasPorPagar();

        // La guardia debe impedir que se recree nada
        $cxpDespues = CuentaPorPagar::where('id_orden_compra', $orden->id)->count();
        $this->assertEquals(0, $cxpDespues, 'sincronizarCuentasPorPagar NO debe recrear CxP en OC anulada');
    }
}
