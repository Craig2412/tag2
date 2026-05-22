<?php

namespace Tests\Feature;

use App\Events\CotizacionGuardado;
use App\Models\Atencion;
use App\Models\Cotizacion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventSystemTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Los catálogos ya existen en la BD de desarrollo (seeders)
    }

    /**
     * CotizacionGuardado debe dispararse exactamente 1 vez al crear una cotización.
     */
    public function test_cotizacion_guardado_se_dispara_una_sola_vez(): void
    {
        Event::fake([CotizacionGuardado::class]);

        $atencion = Atencion::create([
            'id_cliente' => 1,
            'id_personal' => 1,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Evento',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        // CotizacionGuardado debe haberse disparado exactamente 1 vez
        Event::assertDispatched(CotizacionGuardado::class, 1);
    }

    /**
     * CotizacionGuardado NO debe dispararse más de 1 vez al crear una cotización.
     */
    public function test_cotizacion_guardado_no_se_duplica(): void
    {
        Event::fake([CotizacionGuardado::class]);

        $atencion = Atencion::create([
            'id_cliente' => 1,
            'id_personal' => 1,
            'id_origen_atencion' => 1,
            'asunto' => 'Test No Duplicado',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        // Verificar que NO se disparó más de 1 vez
        Event::assertDispatchedTimes(CotizacionGuardado::class, 1);
    }

    /**
     * Al eliminar una cotización, CotizacionGuardado se dispara exactamente 1 vez.
     */
    public function test_cotizacion_guardado_al_eliminar_una_sola_vez(): void
    {
        $atencion = Atencion::create([
            'id_cliente' => 1,
            'id_personal' => 1,
            'id_origen_atencion' => 1,
            'asunto' => 'Test Delete',
            'id_estado_atencion' => 1,
            'id_etapa_comercial' => 1,
        ]);

        $cotizacion = Cotizacion::create([
            'id_atencion' => $atencion->id,
            'id_tipo_cotizacion' => 1,
            'id_estado_cotizacion' => 1,
            'cant_adultos' => 1,
            'cant_menores' => 0,
            'cant_viejos' => 0,
            'id_tasa_cambio' => 1,
            'fecha_vencimiento' => now()->addDays(7),
        ]);

        Event::fake([CotizacionGuardado::class]);

        $cotizacion->delete();

        Event::assertDispatchedTimes(CotizacionGuardado::class, 1);
    }
}
