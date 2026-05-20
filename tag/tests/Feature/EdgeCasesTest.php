<?php

namespace Tests\Feature;

use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\CuentaPorPagar;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\PagoOrdenCompra;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Casos borde del ciclo comercial: transiciones de estado, pagos parciales,
 * reapertura de atenciones, sobrepagos.
 */
class EdgeCasesTest extends TestCase
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
            ['cedula' => 'V-88888888'],
            ['nombre' => 'Cliente Edge', 'apellido' => 'Case', 'telefono' => '+58 412 0000000', 'id_tipo_contribuyente' => 1]
        );
        $this->idCliente = $cliente->id;

        $personal = \App\Models\Personal::firstOrCreate(
            ['usuario_id' => $this->idAdmin],
            ['nombre' => 'Personal Edge', 'apellido' => 'Case', 'cedula' => 'V-22222222', 'telefono' => '+58 412 1111111', 'correo_institucional' => 'e@tag.com', 'porcentaje_comision' => 5.00]
        );
        $this->idPersonal = $personal->id;

        $proveedor = \App\Models\Proveedor::firstOrCreate(
            ['rif' => 'T-11111111-1'],
            ['nombre_empresa' => 'Proveedor Edge', 'razon_comercial' => 'Edge', 'correo_empresa' => 'e@edge.test', 'telefono_empresa' => '02120000001', 'nombre_persona_contacto' => 'Edge Contact', 'id_tipo_contribuyente' => 1, 'tipo_proveedor' => 1]
        );
        $this->idProveedor = $proveedor->id;
    }

    /**
     * El estado financiero de ingreso debe transicionar: pendiente → parcial → pagado.
     */
    public function test_estado_financiero_ingreso_transiciona_correctamente(): void
    {
        // Crear ciclo hasta OC
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Transición Ingreso',
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
            'costo' => 1000.00,
            'monto_gravable' => 1000.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 1000.00,
            'id_tasa_cambio' => 1,
        ]);

        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $this->assertNotNull($orden);

        // Estado inicial: pendiente
        $idPendiente = \App\Models\EstadoFinanciero::where('slug', 'pendiente')->value('id');
        $this->assertEquals($idPendiente, $orden->id_estado_financiero);

        // ── Pago parcial (50%) ──────────────────────────────────
        $pago = Pago::create([
            'id_usuario' => $this->idAdmin,
            'fecha_pago' => now(),
            'monto_total' => 500.00,
            'id_metodo_pago' => 1,
            'nro_comprobante' => 'PARTIAL-001',
            'id_tasa_cambio' => 1,
            'id_estado_conciliacion' => 1,
        ]);

        PagoOrdenCompra::create([
            'id_pago' => $pago->id,
            'id_orden_compra' => $orden->id,
            'monto_asignado' => 500.00,
        ]);

        $orden->refresh();
        $idParcial = \App\Models\EstadoFinanciero::where('slug', 'parcial')->value('id');
        $this->assertEquals($idParcial, $orden->id_estado_financiero, 'Con pago parcial debe ser parcial');

        // ── Pago total (completa) ───────────────────────────────
        $pago2 = Pago::create([
            'id_usuario' => $this->idAdmin,
            'fecha_pago' => now(),
            'monto_total' => 500.00,
            'id_metodo_pago' => 1,
            'nro_comprobante' => 'FINAL-001',
            'id_tasa_cambio' => 1,
            'id_estado_conciliacion' => 1,
        ]);

        PagoOrdenCompra::create([
            'id_pago' => $pago2->id,
            'id_orden_compra' => $orden->id,
            'monto_asignado' => 500.00,
        ]);

        $orden->refresh();
        $idPagado = \App\Models\EstadoFinanciero::where('slug', 'pagado')->value('id');
        $this->assertEquals($idPagado, $orden->id_estado_financiero, 'Con pago total debe ser pagado');
    }

    /**
     * Reabrir una atención cerrada_perdida cuando se agrega una nueva cotización activa.
     */
    public function test_reabrir_atencion_cerrada_perdida_con_nueva_cotizacion(): void
    {
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Reapertura',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        // Crear y rechazar una cotización → cierra perdida
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

        $cot1->update(['id_estado_cotizacion' => 3]); // rechazada
        event(new \App\Events\CotizacionEstatusActualizado($cot1, 1, 3));

        $atencion->refresh();
        $idCerradaPerdida = \App\Models\EstadoAtencion::where('slug', 'cerrada_perdida')->value('id');
        $this->assertEquals($idCerradaPerdida, $atencion->id_estado_atencion, 'Debe cerrarse perdida tras rechazo');

        // ── Crear nueva cotización PENDIENTE ────────────────────
        $cot2 = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1, // pendiente
            'cant_adultos' => 2,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        $atencion->refresh();
        $idAbierta = \App\Models\EstadoAtencion::where('slug', 'abierta')->value('id');
        $this->assertEquals($idAbierta, $atencion->id_estado_atencion, 'Debe reabrirse al crear nueva cotización pendiente');
    }

    /**
     * Los saldos de CuentaPorPagar deben reflejar correctamente pagos parciales.
     */
    public function test_saldos_cxp_reflejan_pagos_parciales(): void
    {
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Saldos',
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
        $this->assertNotNull($cxp);
        $this->assertEquals(800.00, (float) $cxp->monto_total);
        $this->assertEquals(800.00, (float) $cxp->saldo_pendiente, 'Saldo inicial debe ser igual al monto');

        // ── Pago parcial al proveedor (300 de 800) ──────────────
        $pagoProv = PagoProveedor::create([
            'id_proveedor' => $this->idProveedor,
            'id_metodo_pago' => 1,
            'monto_total' => 300.00,
            'fecha_pago' => now(),
            'referencia' => 'PARCIAL-EDGE',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pagoProv->id,
            'id_cuenta_por_pagar' => $cxp->id,
            'monto_asignado' => 300.00,
        ]);

        $cxp->refresh();
        $this->assertEquals(500.00, (float) $cxp->saldo_pendiente, 'Saldo debe ser 500 tras pago parcial de 300');

        $idParcial = \App\Models\EstadoFinanciero::where('slug', 'parcial')->value('id');
        $this->assertEquals($idParcial, $cxp->id_estado_financiero, 'CxP debe estar en estado parcial');
    }
}
