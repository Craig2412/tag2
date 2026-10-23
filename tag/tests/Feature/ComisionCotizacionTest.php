<?php

namespace Tests\Feature;

use App\Events\CotizacionEstatusActualizado;
use App\Models\Atencion;
use App\Models\Cliente;
use App\Models\Comision;
use App\Models\Cotizacion;
use App\Models\EstadoCotizacion;
use App\Models\EstadoFinanciero;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorCuenta;
use App\Models\OrdenCompra;
use App\Models\Personal;
use App\Models\Proveedor;
use App\Models\Servicio;
use App\Models\TipoProveedor;
use App\Models\Usuario;
use App\Services\ComisionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComisionCotizacionTest extends TestCase
{
    use DatabaseTransactions;

    private Cotizacion $cotizacion;

    private Proveedor $comisionista;

    private Usuario $usuario;

    private int $idEstadoFinanciero;

    private int $idAprobada;

    private int $idRechazada;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::firstOrCreate(
            ['correo' => 'comision.test@example.com'],
            ['nombre_usuario' => 'Comision Test', 'clave' => bcrypt('test')]
        );
        $this->usuario = $usuario;

        $cliente = Cliente::firstOrCreate(
            ['cedula' => 'V-88888888'],
            ['nombre' => 'Cliente', 'apellido' => 'Comision', 'telefono' => '+58 412 0000000', 'id_tipo_contribuyente' => 1]
        );

        $personal = Personal::firstOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'nombre' => 'Personal',
                'apellido' => 'Comision',
                'cedula' => 'V-22222222',
                'telefono' => '+58 412 2222222',
                'correo_institucional' => 'comision@tag.com',
                'porcentaje_comision' => 5.00,
            ]
        );

        $atencion = Atencion::create([
            'id_cliente' => $cliente->id,
            'id_personal' => $personal->id,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Comisión',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $tipoComisionista = TipoProveedor::firstOrCreate(['tipo_proveedor' => 'Comisionista']);

        $this->comisionista = Proveedor::create([
            'nombre_empresa' => 'Comisionista Test',
            'razon_comercial' => 'Comisionista Test',
            'rif' => 'T-11111111-1',
            'correo_empresa' => 'comisionista@test.com',
            'telefono_empresa' => '02120000000',
            'nombre_persona_contacto' => 'Contacto',
            'id_tipo_contribuyente' => 1,
            'tipo_proveedor' => $tipoComisionista->id,
        ]);

        $proveedorServicio = Proveedor::firstOrCreate(
            ['rif' => 'T-00000000-0'],
            [
                'nombre_empresa' => 'Proveedor Test',
                'razon_comercial' => 'ProvTest',
                'correo_empresa' => 'p@test.com',
                'telefono_empresa' => '02120000000',
                'nombre_persona_contacto' => 'Contacto',
                'id_tipo_contribuyente' => 1,
                'tipo_proveedor' => 1,
            ]
        );

        $this->cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => EstadoCotizacion::where('slug', 'pendiente')->value('id'),
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        Servicio::create([
            'id_cotizacion' => $this->cotizacion->id,
            'id_tipo_servicio' => 1,
            'id_proveedor' => $proveedorServicio->id,
            'costo' => 500.00,
            'monto_gravable' => 1000.00,
            'monto_no_sujeto' => 0,
            'total_servicio' => 1000.00,
            'id_tasa_cambio' => 1,
        ]);

        $this->idEstadoFinanciero = (int) EstadoFinanciero::where('slug', 'pendiente')->value('id');
        $this->idAprobada = (int) EstadoCotizacion::where('slug', 'aprobada')->value('id');
        $this->idRechazada = (int) EstadoCotizacion::where('slug', 'rechazada')->value('id');
    }

    public function test_aprobacion_con_comision_crea_comision_y_cuenta_por_pagar(): void
    {
        $this->aprobarConComision(10);

        $comision = Comision::where('id_cotizacion', $this->cotizacion->id)->firstOrFail();

        $this->assertSame('porcentaje', $comision->tipo);
        $this->assertEquals(1000.00, (float) $comision->base);
        $this->assertEquals(100.00, (float) $comision->monto);
        $this->assertSame(Comision::ESTADO_DEVENGADA, $comision->estado);

        $this->assertDatabaseHas('cuentas_por_pagar', [
            'id' => $comision->id_cuenta_por_pagar,
            'id_orden_compra' => null,
            'origen' => 'comision',
            'id_cotizacion' => $this->cotizacion->id,
            'id_proveedor' => $this->comisionista->id,
            'monto_total' => 100.00,
            'saldo_pendiente' => 100.00,
        ]);
    }

    public function test_aprobacion_sin_comision_no_crea_registros(): void
    {
        event(new CotizacionEstatusActualizado(
            cotizacion: $this->cotizacion,
            estatusAnterior: $this->cotizacion->id_estado_cotizacion,
            estatusNuevo: $this->idAprobada,
        ));

        $this->assertDatabaseCount('comisiones', 0);
    }

    public function test_aprobacion_es_idempotente(): void
    {
        $this->aprobarConComision(10);
        $this->aprobarConComision(10);

        $this->assertSame(1, Comision::where('id_cotizacion', $this->cotizacion->id)->count());
    }

    public function test_comision_sobre_cotizacion_ya_aprobada(): void
    {
        $this->cotizacion->update(['id_estado_cotizacion' => $this->idAprobada]);

        $this->usuario->givePermissionTo(['edit:cotizaciones', 'create:cuentas_por_pagar']);
        Sanctum::actingAs($this->usuario);

        $this->putJson("/api/cotizaciones/{$this->cotizacion->id}", [
            'id_estado_cotizacion' => $this->idAprobada,
            'comision' => [
                'id_proveedor_comisionista' => $this->comisionista->id,
                'tipo' => Comision::TIPO_FIJO,
                'valor' => 250,
                'id_estado_financiero' => $this->idEstadoFinanciero,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('comisiones', [
            'id_cotizacion' => $this->cotizacion->id,
            'tipo' => Comision::TIPO_FIJO,
            'monto' => 250.00,
            'estado' => Comision::ESTADO_DEVENGADA,
        ]);
    }

    public function test_pago_total_marca_comision_como_pagada(): void
    {
        $this->aprobarConComision(10);
        $comision = Comision::where('id_cotizacion', $this->cotizacion->id)->firstOrFail();

        $pago = PagoProveedor::create([
            'id_proveedor' => $this->comisionista->id,
            'id_metodo_pago' => 1,
            'monto_total' => 100.00,
            'fecha_pago' => now(),
            'referencia' => 'PAGO-COM-TOTAL',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pago->id,
            'id_cuenta_por_pagar' => $comision->id_cuenta_por_pagar,
            'monto_asignado' => 100.00,
        ]);

        $this->assertSame(Comision::ESTADO_PAGADA, $comision->fresh()->estado);
    }

    public function test_rechazo_anula_comision_y_cuenta_por_pagar(): void
    {
        $this->aprobarConComision(10);
        $comision = Comision::where('id_cotizacion', $this->cotizacion->id)->firstOrFail();

        event(new CotizacionEstatusActualizado(
            cotizacion: $this->cotizacion,
            estatusAnterior: $this->idAprobada,
            estatusNuevo: $this->idRechazada,
        ));

        $comision->refresh();
        $this->assertSame(Comision::ESTADO_ANULADA, $comision->estado);
        $this->assertNotNull($comision->anulada_at);
        $this->assertSoftDeleted('cuentas_por_pagar', ['id' => $comision->id_cuenta_por_pagar]);
    }

    public function test_rechazo_bloqueado_si_la_comision_tiene_pagos(): void
    {
        $this->aprobarConComision(10);
        $comision = Comision::where('id_cotizacion', $this->cotizacion->id)->firstOrFail();

        $pago = PagoProveedor::create([
            'id_proveedor' => $this->comisionista->id,
            'id_metodo_pago' => 1,
            'monto_total' => 50.00,
            'fecha_pago' => now(),
            'referencia' => 'PAGO-COM-001',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pago->id,
            'id_cuenta_por_pagar' => $comision->id_cuenta_por_pagar,
            'monto_asignado' => 50.00,
        ]);

        $this->expectException(ValidationException::class);

        app(ComisionService::class)->anularPorCotizacion($this->cotizacion, 'Cotización rechazada');
    }

    public function test_aprobacion_via_api_crea_comision_atomicamente(): void
    {
        $this->usuario->givePermissionTo(['edit:cotizaciones', 'create:cuentas_por_pagar']);
        Sanctum::actingAs($this->usuario);

        $response = $this->putJson("/api/cotizaciones/{$this->cotizacion->id}", [
            'id_estado_cotizacion' => $this->idAprobada,
            'comision' => [
                'id_proveedor_comisionista' => $this->comisionista->id,
                'tipo' => Comision::TIPO_PORCENTAJE,
                'valor' => 10,
                'id_estado_financiero' => $this->idEstadoFinanciero,
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('comisiones', [
            'id_cotizacion' => $this->cotizacion->id,
            'id_proveedor_comisionista' => $this->comisionista->id,
            'estado' => Comision::ESTADO_DEVENGADA,
        ]);
        $this->assertDatabaseHas('cuentas_por_pagar', [
            'origen' => 'comision',
            'id_cotizacion' => $this->cotizacion->id,
            'id_orden_compra' => null,
        ]);
    }

    public function test_rechazo_via_api_bloquea_y_hace_rollback_si_hay_pagos(): void
    {
        $this->usuario->givePermissionTo(['edit:cotizaciones', 'create:cuentas_por_pagar']);
        Sanctum::actingAs($this->usuario);

        $this->putJson("/api/cotizaciones/{$this->cotizacion->id}", [
            'id_estado_cotizacion' => $this->idAprobada,
            'comision' => [
                'id_proveedor_comisionista' => $this->comisionista->id,
                'tipo' => Comision::TIPO_PORCENTAJE,
                'valor' => 10,
                'id_estado_financiero' => $this->idEstadoFinanciero,
            ],
        ])->assertOk();

        $comision = Comision::where('id_cotizacion', $this->cotizacion->id)->firstOrFail();

        $pago = PagoProveedor::create([
            'id_proveedor' => $this->comisionista->id,
            'id_metodo_pago' => 1,
            'monto_total' => 50.00,
            'fecha_pago' => now(),
            'referencia' => 'PAGO-COM-API-001',
            'id_tasa_cambio' => 1,
        ]);

        PagoProveedorCuenta::create([
            'id_pago_proveedor' => $pago->id,
            'id_cuenta_por_pagar' => $comision->id_cuenta_por_pagar,
            'monto_asignado' => 50.00,
        ]);

        $this->putJson("/api/cotizaciones/{$this->cotizacion->id}", [
            'id_estado_cotizacion' => $this->idRechazada,
        ])->assertStatus(422);

        $this->cotizacion->refresh();
        $this->assertSame($this->idAprobada, (int) $this->cotizacion->id_estado_cotizacion);
        $this->assertSame(Comision::ESTADO_DEVENGADA, $comision->fresh()->estado);
        $this->assertNotSoftDeleted('cuentas_por_pagar', ['id' => $comision->id_cuenta_por_pagar]);

        $orden = OrdenCompra::where('id_cotizacion', $this->cotizacion->id)->first();
        $this->assertNotNull($orden);
        $this->assertNotSame('anulada', $orden->estadoOrdenCompra?->slug);
    }

    private function aprobarConComision(float $valor): void
    {
        event(new CotizacionEstatusActualizado(
            cotizacion: $this->cotizacion,
            estatusAnterior: $this->cotizacion->id_estado_cotizacion,
            estatusNuevo: $this->idAprobada,
            comision: [
                'id_proveedor_comisionista' => $this->comisionista->id,
                'tipo' => Comision::TIPO_PORCENTAJE,
                'valor' => $valor,
                'id_estado_financiero' => $this->idEstadoFinanciero,
            ],
        ));
    }
}
