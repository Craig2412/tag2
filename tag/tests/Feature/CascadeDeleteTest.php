<?php

namespace Tests\Feature;

use App\Events\CotizacionGuardado;
use App\Models\Atencion;
use App\Models\Cotizacion;
use App\Models\CuentaPorPagar;
use App\Models\OrdenCompra;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Pruebas de eliminación en cascada (soft-delete).
 *
 * Verifica que al eliminar una Atención, Cotización u Orden de Compra,
 * los registros hijos se eliminan en cascada correctamente y que
 * los guards anti-phantom evitan trabajo innecesario.
 */
class CascadeDeleteTest extends TestCase
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
            ['correo' => 'cascade@example.com'],
            ['nombre_usuario' => 'Cascade Test', 'clave' => bcrypt('test')]
        );
        $this->idAdmin = $usuario->id;

        $cliente = \App\Models\Cliente::firstOrCreate(
            ['cedula' => 'V-77777777'],
            ['nombre' => 'Cliente Cascade', 'apellido' => 'Test', 'telefono' => '+58 412 7777777', 'id_tipo_contribuyente' => 1]
        );
        $this->idCliente = $cliente->id;

        $personal = \App\Models\Personal::firstOrCreate(
            ['usuario_id' => $this->idAdmin],
            ['nombre' => 'Personal Cascade', 'apellido' => 'Test', 'cedula' => 'V-33333333', 'telefono' => '+58 412 3333333', 'correo_institucional' => 'c@tag.com', 'porcentaje_comision' => 5.00]
        );
        $this->idPersonal = $personal->id;

        $proveedor = \App\Models\Proveedor::firstOrCreate(
            ['rif' => 'T-22222222-2'],
            ['nombre_empresa' => 'Proveedor Cascade', 'razon_comercial' => 'Cascade', 'correo_empresa' => 'c@cascade.test', 'telefono_empresa' => '02120000002', 'nombre_persona_contacto' => 'Cascade Contact', 'id_tipo_contribuyente' => 1, 'tipo_proveedor' => 1]
        );
        $this->idProveedor = $proveedor->id;
    }

    /**
     * Crea un ciclo completo hasta CxP con pago a proveedor.
     * Retorna [atencion, cotizacion, orden, cxp].
     */
    private function crearCicloCompleto(): array
    {
        $atencion = Atencion::create([
            'id_cliente' => $this->idCliente,
            'id_personal' => $this->idPersonal,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Cascada',
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
            'costo' => 400.00,
            'monto_gravable' => 400.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 400.00,
            'id_tasa_cambio' => 1,
        ]);

        // Disparar generación de OC
        event(new \App\Events\CotizacionEstatusActualizado($cotizacion, 1, 2));

        $orden = OrdenCompra::where('id_cotizacion', $cotizacion->id)->first();
        $cxp = CuentaPorPagar::where('id_orden_compra', $orden->id)->first();

        // Crear pago a proveedor para tener pivotes
        $pagoProv = PagoProveedor::create([
            'id_proveedor' => $this->idProveedor,
            'id_metodo_pago' => 1,
            'monto_total' => 200.00,
            'fecha_pago' => now(),
            'referencia' => 'CASCADE-001',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pagoProv->id,
            'id_cuenta_por_pagar' => $cxp->id,
            'monto_asignado' => 200.00,
        ]);

        return [$atencion->fresh(), $cotizacion->fresh(), $orden->fresh(), $cxp->fresh()];
    }

    // ─────────────────────────────────────────────────────────────
    //  CASO 1: Eliminar Atención → cascada completa
    // ─────────────────────────────────────────────────────────────

    /**
     * Al eliminar una Atención, todas sus cotizaciones deben ser soft-delete.
     */
    public function test_eliminar_atencion_cascada_cotizaciones(): void
    {
        [$atencion, $cotizacion] = $this->crearCicloCompleto();

        $atencion->delete();

        $this->assertNotNull($atencion->fresh()->deleted_at, 'Atención debe tener soft-delete');
        $this->assertNotNull($cotizacion->fresh()->deleted_at, 'Cotización debe tener soft-delete por cascada');
    }

    /**
     * Al eliminar una Atención, los servicios de sus cotizaciones deben ser soft-delete.
     */
    public function test_eliminar_atencion_cascada_servicios(): void
    {
        [$atencion, $cotizacion] = $this->crearCicloCompleto();

        $servicio = Servicio::where('id_cotizacion', $cotizacion->id)->first();
        $this->assertNotNull($servicio, 'Debe existir al menos un servicio');

        $atencion->delete();

        $this->assertNotNull($servicio->fresh()->deleted_at, 'Servicio debe tener soft-delete por cascada');
    }

    /**
     * Al eliminar una Atención, la Orden de Compra debe ser soft-delete.
     */
    public function test_eliminar_atencion_cascada_orden_compra(): void
    {
        [$atencion, $cotizacion, $orden] = $this->crearCicloCompleto();

        $atencion->delete();

        $this->assertNotNull($orden->fresh()->deleted_at, 'Orden de compra debe tener soft-delete por cascada');
    }

    /**
     * Al eliminar una Atención, las Cuentas por Pagar deben ser soft-delete
     * y sus pivotes de pago a proveedor deben ser eliminados (hard delete).
     */
    public function test_eliminar_atencion_cascada_cuentas_por_pagar_y_pivotes(): void
    {
        [$atencion, $cotizacion, $orden, $cxp] = $this->crearCicloCompleto();

        $pivoteAntes = PagoProveedorCuenta::where('id_cuenta_por_pagar', $cxp->id)->exists();
        $this->assertTrue($pivoteAntes, 'Debe existir pivote de pago proveedor');

        $atencion->delete();

        // CxP debe tener soft-delete
        $this->assertNotNull($cxp->fresh()->deleted_at, 'Cuenta por pagar debe tener soft-delete');

        // Pivote pago_proveedor_cuentas debe haber sido eliminado (hard delete)
        $pivoteExiste = PagoProveedorCuenta::where('id_cuenta_por_pagar', $cxp->id)->exists();
        $this->assertFalse($pivoteExiste, 'Pivote de pago proveedor debe ser eliminado (hard delete)');
    }

    /**
     * Al eliminar una Atención, CotizacionGuardado NO debe dispararse.
     * (El guard en CotizacionObserver lo evita porque el padre Atencion está trashed.)
     */
    public function test_eliminar_atencion_no_dispara_cotizacion_guardado(): void
    {
        [$atencion] = $this->crearCicloCompleto();

        Event::fake([CotizacionGuardado::class]);

        $atencion->delete();

        Event::assertNotDispatched(CotizacionGuardado::class);
    }

    // ─────────────────────────────────────────────────────────────
    //  CASO 2: Eliminar Cotización standalone → cascada parcial
    // ─────────────────────────────────────────────────────────────

    /**
     * Al eliminar una Cotización standalone, sus servicios deben ser soft-delete.
     */
    public function test_eliminar_cotizacion_standalone_cascada_servicios(): void
    {
        [$atencion, $cotizacion] = $this->crearCicloCompleto();

        $servicio = Servicio::where('id_cotizacion', $cotizacion->id)->first();

        $cotizacion->delete();

        $this->assertNotNull($servicio->fresh()->deleted_at, 'Servicio debe tener soft-delete');
        $this->assertNotNull($cotizacion->fresh()->deleted_at, 'Cotización debe tener soft-delete');
    }

    /**
     * Al eliminar una Cotización standalone, su Orden de Compra debe ser soft-delete.
     */
    public function test_eliminar_cotizacion_standalone_cascada_orden_compra(): void
    {
        [$atencion, $cotizacion, $orden] = $this->crearCicloCompleto();

        $cotizacion->delete();

        $this->assertNotNull($orden->fresh()->deleted_at, 'Orden de compra debe tener soft-delete por cascada');
    }

    /**
     * Al eliminar una Cotización standalone, CotizacionGuardado SÍ debe dispararse.
     * (La Atención padre NO está trashed → guard permite el dispatch.)
     */
    public function test_eliminar_cotizacion_standalone_dispara_cotizacion_guardado(): void
    {
        [$atencion, $cotizacion] = $this->crearCicloCompleto();

        Event::fake([CotizacionGuardado::class]);

        $cotizacion->delete();

        Event::assertDispatched(CotizacionGuardado::class, 1);
    }

    // ─────────────────────────────────────────────────────────────
    //  CASO 3: Eliminar OC standalone → comportamiento existente
    // ─────────────────────────────────────────────────────────────

    /**
     * Al eliminar una OC standalone (no en cascada), la cotización origen
     * debe marcarse como "rechazada".
     */
    public function test_eliminar_oc_standalone_marca_cotizacion_rechazada(): void
    {
        [$atencion, $cotizacion, $orden] = $this->crearCicloCompleto();

        $idRechazada = \App\Models\EstadoCotizacion::where('slug', 'rechazada')->value('id');

        $orden->delete();

        $cotizacion->refresh();
        $this->assertEquals($idRechazada, (int) $cotizacion->id_estado_cotizacion, 'Cotización debe marcarse como rechazada');
        $this->assertNull($cotizacion->deleted_at, 'Cotización NO debe ser soft-delete en borrado standalone de OC');
    }

    /**
     * Al eliminar una OC standalone, las CxP deben ser soft-delete
     * y sus pivotes eliminados (hard delete).
     */
    public function test_eliminar_oc_standalone_limpia_cxp_y_pivotes(): void
    {
        [$atencion, $cotizacion, $orden, $cxp] = $this->crearCicloCompleto();

        $orden->delete();

        $this->assertNotNull($cxp->fresh()->deleted_at, 'CxP debe tener soft-delete');

        $pivoteExiste = PagoProveedorCuenta::where('id_cuenta_por_pagar', $cxp->id)->exists();
        $this->assertFalse($pivoteExiste, 'Pivote debe ser eliminado (hard delete)');
    }

    // ─────────────────────────────────────────────────────────────
    //  CASO 4: Eliminar OC en cascada → guard anti-phantom
    // ─────────────────────────────────────────────────────────────

    /**
     * Cuando una OC se elimina por cascada (desde Cotización),
     * la cotización NO debe marcarse como "rechazada".
     * (El guard en OrdenCompraObserver lo evita porque la cotización está trashed.)
     */
    public function test_eliminar_oc_en_cascada_no_marca_cotizacion_rechazada(): void
    {
        [$atencion, $cotizacion, $orden] = $this->crearCicloCompleto();

        $idRechazada = \App\Models\EstadoCotizacion::where('slug', 'rechazada')->value('id');
        $estadoAntes = (int) $cotizacion->id_estado_cotizacion;

        // Eliminar cotización → OC se elimina en cascada
        $cotizacion->delete();

        $cotizacion->refresh();
        // La cotización está soft-delete, su estado NO debe haber cambiado a "rechazada"
        $this->assertNotEquals($idRechazada, (int) $cotizacion->id_estado_cotizacion, 'Cotización NO debe marcarse rechazada si ya está eliminada');
    }

    // ─────────────────────────────────────────────────────────────
    //  CASO 5: Reversión de saldos CxP al eliminar OC
    // ─────────────────────────────────────────────────────────────

    /**
     * Al eliminar una OC (standalone o cascada), el saldo pendiente de la CxP
     * debe revertirse con los pagos ya asignados ANTES del soft-delete.
     */
    public function test_eliminar_oc_revierte_saldo_cxp_antes_de_borrar(): void
    {
        [$atencion, $cotizacion, $orden, $cxp] = $this->crearCicloCompleto();

        $saldoAntesDelete = (float) $cxp->saldo_pendiente;
        // El pago parcial de 200 ya fue aplicado, así que el saldo es < monto_total
        $this->assertLessThan((float) $cxp->monto_total, $saldoAntesDelete, 'Saldo debe reflejar pago parcial');

        $orden->delete();

        // La CxP está soft-delete, pero su saldo fue revertido antes del delete
        $cxpTrashed = CuentaPorPagar::withTrashed()->find($cxp->id);
        $this->assertNotNull($cxpTrashed->deleted_at, 'CxP debe tener soft-delete');
        $this->assertEquals((float) $cxpTrashed->monto_total, (float) $cxpTrashed->saldo_pendiente, 'Saldo debe estar revertido al monto total');
    }
}
