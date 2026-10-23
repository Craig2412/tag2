<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Origen;
use App\Models\Usuario;
use App\Services\ClienteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AtencionNestedClienteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reutiliza_cliente_existente_por_cedula(): void
    {
        $cedula = 'TEST-'.uniqid();
        $existing = Cliente::create([
            'nombre' => 'Cliente',
            'apellido' => 'Existente',
            'cedula' => $cedula,
        ]);

        $result = app(ClienteService::class)->findOrCreateCliente([
            'nombre' => 'Otro',
            'apellido' => 'Nombre',
            'cedula' => $cedula,
        ]);

        $this->assertSame($existing->id, $result->id);
    }

    public function test_crea_cliente_cuando_la_cedula_no_existe(): void
    {
        $cedula = 'TEST-'.uniqid();

        $result = app(ClienteService::class)->findOrCreateCliente([
            'nombre' => 'Cliente',
            'apellido' => 'Nuevo',
            'cedula' => $cedula,
            'telefono' => '04121234567',
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $result->id,
            'cedula' => $cedula,
            'nombre' => 'Cliente',
            'apellido' => 'Nuevo',
        ]);
    }

    public function test_endpoint_crea_atencion_con_cliente_anidado(): void
    {
        $admin = Usuario::role('admin')->firstOrFail();
        $cedula = 'TEST-'.uniqid();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/atenciones', [
            'cliente' => [
                'nombre' => 'Cliente',
                'apellido' => 'Anidado',
                'cedula' => $cedula,
                'telefono' => '04121234567',
            ],
            'id_origen_atencion' => Origen::query()->value('id'),
            'asunto' => 'Atención con cliente anidado',
            'notas_adicionales' => 'Prueba de creación anidada',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.cliente.cedula', $cedula);
        $this->assertDatabaseHas('clientes', ['cedula' => $cedula]);
        $this->assertDatabaseHas('atenciones', ['asunto' => 'Atención con cliente anidado']);
    }

    public function test_endpoint_reutiliza_cliente_existente_por_cedula(): void
    {
        $admin = Usuario::role('admin')->firstOrFail();
        $cedula = 'TEST-'.uniqid();
        $existing = Cliente::create([
            'nombre' => 'Cliente',
            'apellido' => 'Existente',
            'cedula' => $cedula,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/atenciones', [
            'cliente' => [
                'nombre' => 'Cliente',
                'apellido' => 'Nuevo',
                'cedula' => $cedula,
            ],
            'id_origen_atencion' => Origen::query()->value('id'),
            'asunto' => 'Atención reutilizando cliente',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.id_cliente', $existing->id);
    }

    public function test_endpoint_rechaza_cliente_anidado_sin_permiso_de_clientes(): void
    {
        $user = Usuario::create([
            'nombre_usuario' => 'test-'.uniqid(),
            'correo' => 'test-'.uniqid().'@example.com',
            'clave' => Hash::make('password123'),
            'esta_activo' => true,
        ]);
        $user->givePermissionTo('create:atenciones');

        $cedula = 'TEST-'.uniqid();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/atenciones', [
            'cliente' => [
                'nombre' => 'Cliente',
                'apellido' => 'Sin Permiso',
                'cedula' => $cedula,
            ],
            'id_origen_atencion' => Origen::query()->value('id'),
            'asunto' => 'Atención sin permiso de clientes',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('clientes', ['cedula' => $cedula]);
    }
}
