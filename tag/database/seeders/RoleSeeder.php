<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ── Usuarios ──
            'view:usuarios', 'create:usuarios', 'edit:usuarios', 'delete:usuarios',
            'view:usuarios:todas', 'edit:usuarios:todas', 'delete:usuarios:todas',
            // ── Personal ──
            'view:personal', 'create:personal', 'edit:personal', 'delete:personal',
            'view:personal:todas', 'edit:personal:todas', 'delete:personal:todas',
            // ── Clientes ──
            'view:clientes', 'create:clientes', 'edit:clientes', 'delete:clientes',
            'view:clientes:todas', 'edit:clientes:todas', 'delete:clientes:todas',
            // ── Tipos de Proveedores ──
            'view:tipos_proveedores', 'create:tipos_proveedores', 'edit:tipos_proveedores', 'delete:tipos_proveedores',
            'view:tipos_proveedores:todas', 'edit:tipos_proveedores:todas', 'delete:tipos_proveedores:todas',
            // ── Proveedores ──
            'view:proveedores', 'create:proveedores', 'edit:proveedores', 'delete:proveedores',
            'view:proveedores:todas', 'edit:proveedores:todas', 'delete:proveedores:todas',
            // ── Tipo Servicio ──
            'view:tipo_servicio', 'create:tipo_servicio', 'edit:tipo_servicio', 'delete:tipo_servicio',
            'view:tipo_servicio:todas', 'edit:tipo_servicio:todas', 'delete:tipo_servicio:todas',
            // ── Tasas de Cambio ──
            'view:tasas_cambio', 'create:tasas_cambio', 'edit:tasas_cambio', 'delete:tasas_cambio',
            'view:tasas_cambio:todas', 'edit:tasas_cambio:todas', 'delete:tasas_cambio:todas',
            // ── Servicios ──
            'view:servicios', 'create:servicios', 'edit:servicios', 'delete:servicios',
            'view:servicios:todas', 'edit:servicios:todas', 'delete:servicios:todas',
            // ── Orígenes ──
            'view:origenes', 'create:origenes', 'edit:origenes', 'delete:origenes',
            'view:origenes:todas', 'edit:origenes:todas', 'delete:origenes:todas',
            // ── Atenciones ──
            'view:atenciones', 'create:atenciones', 'edit:atenciones', 'delete:atenciones',
            'view:atenciones:todas', 'edit:atenciones:todas', 'delete:atenciones:todas',
            // ── Atenciones Personal ──
            'view:atenciones_personal', 'create:atenciones_personal', 'edit:atenciones_personal', 'delete:atenciones_personal',
            'view:atenciones_personal:todas', 'edit:atenciones_personal:todas', 'delete:atenciones_personal:todas',
            // ── Cotizaciones ──
            'view:cotizaciones', 'create:cotizaciones', 'edit:cotizaciones', 'delete:cotizaciones',
            'view:cotizaciones:todas', 'edit:cotizaciones:todas', 'delete:cotizaciones:todas',
            // ── Tipos de Cotizaciones ──
            'view:tipos_cotizaciones', 'create:tipos_cotizaciones', 'edit:tipos_cotizaciones', 'delete:tipos_cotizaciones',
            'view:tipos_cotizaciones:todas', 'edit:tipos_cotizaciones:todas', 'delete:tipos_cotizaciones:todas',
            // ── Servicios de Cotizaciones ──
            'view:servicios_cotizaciones', 'create:servicios_cotizaciones', 'edit:servicios_cotizaciones', 'delete:servicios_cotizaciones',
            'view:servicios_cotizaciones:todas', 'edit:servicios_cotizaciones:todas', 'delete:servicios_cotizaciones:todas',
            // ── Métodos de Pago ──
            'view:metodos_pago', 'create:metodos_pago', 'edit:metodos_pago', 'delete:metodos_pago',
            'view:metodos_pago:todas', 'edit:metodos_pago:todas', 'delete:metodos_pago:todas',
            // ── Pagos ──
            'view:pagos', 'create:pagos', 'edit:pagos', 'delete:pagos',
            'view:pagos:todas', 'edit:pagos:todas', 'delete:pagos:todas',
            // ── Tipos de Contribuyentes ──
            'view:tipos_contribuyentes', 'create:tipos_contribuyentes', 'edit:tipos_contribuyentes', 'delete:tipos_contribuyentes',
            'view:tipos_contribuyentes:todas', 'edit:tipos_contribuyentes:todas', 'delete:tipos_contribuyentes:todas',
            // ── Empresas ──
            'view:empresas', 'create:empresas', 'edit:empresas', 'delete:empresas',
            'view:empresas:todas', 'edit:empresas:todas', 'delete:empresas:todas',
            // ── Personal Empresas ──
            'view:personal_empresas', 'create:personal_empresas', 'edit:personal_empresas', 'delete:personal_empresas',
            'view:personal_empresas:todas', 'edit:personal_empresas:todas', 'delete:personal_empresas:todas',
            // ── Pagos Proveedores ──
            'view:pagos_proveedores', 'create:pagos_proveedores', 'edit:pagos_proveedores', 'delete:pagos_proveedores',
            'view:pagos_proveedores:todas', 'edit:pagos_proveedores:todas', 'delete:pagos_proveedores:todas',
            // ── Cuentas Proveedores ──
            'view:cuentas_proveedores', 'create:cuentas_proveedores', 'edit:cuentas_proveedores', 'delete:cuentas_proveedores',
            'view:cuentas_proveedores:todas', 'edit:cuentas_proveedores:todas', 'delete:cuentas_proveedores:todas',
            // ── Configuraciones del Sistema ──
            'view:configuraciones_sistema', 'create:configuraciones_sistema', 'edit:configuraciones_sistema', 'delete:configuraciones_sistema',
            'view:configuraciones_sistema:todas', 'edit:configuraciones_sistema:todas', 'delete:configuraciones_sistema:todas',
            // ── Temporalidades ──
            'view:temporalidades', 'create:temporalidades', 'edit:temporalidades', 'delete:temporalidades',
            'view:temporalidades:todas', 'edit:temporalidades:todas', 'delete:temporalidades:todas',
            // ── Entidades Bancarias ──
            'view:entidades_bancarias', 'create:entidades_bancarias', 'edit:entidades_bancarias', 'delete:entidades_bancarias',
            'view:entidades_bancarias:todas', 'edit:entidades_bancarias:todas', 'delete:entidades_bancarias:todas',
            // ── Metas ──
            'view:metas', 'create:metas', 'edit:metas', 'delete:metas',
            'view:metas:todas', 'edit:metas:todas', 'delete:metas:todas',
            // ── Metas Personal ──
            'view:metas_personal', 'create:metas_personal', 'edit:metas_personal', 'delete:metas_personal',
            'view:metas_personal:todas', 'edit:metas_personal:todas', 'delete:metas_personal:todas',
            // ── Órdenes de Compra ──
            'view:ordenes_compra', 'create:ordenes_compra', 'edit:ordenes_compra', 'delete:ordenes_compra',
            'view:ordenes_compra:todas', 'edit:ordenes_compra:todas', 'delete:ordenes_compra:todas',
            // ── Tasas ──
            'view:tasas', 'create:tasas', 'edit:tasas', 'delete:tasas',
            'view:tasas:todas', 'edit:tasas:todas', 'delete:tasas:todas',
            // ── Cuentas por Pagar ──
            'view:cuentas_por_pagar', 'create:cuentas_por_pagar', 'edit:cuentas_por_pagar', 'delete:cuentas_por_pagar',
            'view:cuentas_por_pagar:todas', 'edit:cuentas_por_pagar:todas', 'delete:cuentas_por_pagar:todas',
            // ── Clientes Empresas ──
            'view:clientes_empresas', 'create:clientes_empresas', 'edit:clientes_empresas', 'delete:clientes_empresas',
            'view:clientes_empresas:todas', 'edit:clientes_empresas:todas', 'delete:clientes_empresas:todas',
            // ── Pagos Órdenes de Compra ──
            'view:pagos_ordenes_compra', 'create:pagos_ordenes_compra', 'edit:pagos_ordenes_compra', 'delete:pagos_ordenes_compra',
            'view:pagos_ordenes_compra:todas', 'edit:pagos_ordenes_compra:todas', 'delete:pagos_ordenes_compra:todas',
            // ── Estatus ──
            'view:estatus', 'create:estatus', 'edit:estatus', 'delete:estatus',
            'view:estatus:todas', 'edit:estatus:todas', 'delete:estatus:todas',
            // ── Logros Personal (solo view) ──
            'view:logros_personal',
            'view:logros_personal:todas',
            // ── Audit Logs (solo admin, no requiere :todas) ──
            'view:audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $userRole = Role::findOrCreate('user', 'web');
        $personalRole = Role::findOrCreate('personal', 'web');
        $clienteRole = Role::findOrCreate('cliente', 'web');

        $adminRole->syncPermissions($permissions);

        // Crear Usuarios Base (Estructura Minimalista)
        $users = [
            [
                'nombre_usuario' => 'Admin Root',
                'correo' => 'admin@example.com',
                'clave' => Hash::make('password'),
                'role' => $adminRole,
            ],
            [
                'nombre_usuario' => 'Usuario Demo',
                'correo' => 'user@example.com',
                'clave' => Hash::make('password'),
                'role' => $userRole,
            ],
            [
                'nombre_usuario' => 'Personal Comercial',
                'correo' => 'personal@example.com',
                'clave' => Hash::make('password'),
                'role' => $personalRole,
            ],
            [
                'nombre_usuario' => 'Cliente Demo',
                'correo' => 'cliente@example.com',
                'clave' => Hash::make('password'),
                'role' => $clienteRole,
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = Usuario::firstOrCreate(['correo' => $userData['correo']], $userData);
            $user->syncRoles([$role]);
        }
    }
}
