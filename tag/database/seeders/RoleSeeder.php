<?php

namespace Database\Seeders;

use App\Models\Estatus;
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
            'view:usuarios',
            'create:usuarios',
            'edit:usuarios',
            'delete:usuarios',
            'view:personal',
            'create:personal',
            'edit:personal',
            'delete:personal',
            'view:clientes',
            'create:clientes',
            'edit:clientes',
            'delete:clientes',
            'view:tipos_proveedores',
            'create:tipos_proveedores',
            'edit:tipos_proveedores',
            'delete:tipos_proveedores',
            'view:proveedores',
            'create:proveedores',
            'edit:proveedores',
            'delete:proveedores',
            'view:tipo_servicio',
            'create:tipo_servicio',
            'edit:tipo_servicio',
            'delete:tipo_servicio',
            'view:tasas_cambio',
            'create:tasas_cambio',
            'edit:tasas_cambio',
            'delete:tasas_cambio',
            'view:servicios',
            'create:servicios',
            'edit:servicios',
            'delete:servicios',
            'view:origenes',
            'create:origenes',
            'edit:origenes',
            'delete:origenes',
            'view:atenciones',
            'create:atenciones',
            'edit:atenciones',
            'delete:atenciones',
            'view:atenciones_personal',
            'create:atenciones_personal',
            'edit:atenciones_personal',
            'delete:atenciones_personal',
            'view:cotizaciones',
            'create:cotizaciones',
            'edit:cotizaciones',
            'delete:cotizaciones',
            'view:tipos_cotizaciones',
            'create:tipos_cotizaciones',
            'edit:tipos_cotizaciones',
            'delete:tipos_cotizaciones',
            'view:servicios_cotizaciones',
            'create:servicios_cotizaciones',
            'edit:servicios_cotizaciones',
            'delete:servicios_cotizaciones',
            'view:metodos_pago',
            'create:metodos_pago',
            'edit:metodos_pago',
            'delete:metodos_pago',
            'view:pagos',
            'create:pagos',
            'edit:pagos',
            'delete:pagos',
            'view:tipos_contribuyentes',
            'create:tipos_contribuyentes',
            'edit:tipos_contribuyentes',
            'delete:tipos_contribuyentes',
            'view:empresas',
            'create:empresas',
            'edit:empresas',
            'delete:empresas',
            'view:personal_empresas',
            'create:personal_empresas',
            'edit:personal_empresas',
            'delete:personal_empresas',
            'view:pagos_proveedores',
            'create:pagos_proveedores',
            'edit:pagos_proveedores',
            'delete:pagos_proveedores',
            'view:cuentas_proveedores',
            'create:cuentas_proveedores',
            'edit:cuentas_proveedores',
            'delete:cuentas_proveedores',
            'view:configuraciones_sistema',
            'create:configuraciones_sistema',
            'edit:configuraciones_sistema',
            'delete:configuraciones_sistema',
            'view:temporalidades',
            'create:temporalidades',
            'edit:temporalidades',
            'delete:temporalidades',
            'view:entidades_bancarias',
            'create:entidades_bancarias',
            'edit:entidades_bancarias',
            'delete:entidades_bancarias',
            'view:metas',
            'create:metas',
            'edit:metas',
            'delete:metas',
            'view:metas_personal',
            'create:metas_personal',
            'edit:metas_personal',
            'delete:metas_personal',
            'view:ordenes_compra',
            'create:ordenes_compra',
            'edit:ordenes_compra',
            'delete:ordenes_compra',
            'view:tasas',
            'create:tasas',
            'edit:tasas',
            'delete:tasas',
            'view:cuentas_por_pagar',
            'create:cuentas_por_pagar',
            'edit:cuentas_por_pagar',
            'delete:cuentas_por_pagar',
            'view:clientes_empresas',
            'create:clientes_empresas',
            'edit:clientes_empresas',
            'delete:clientes_empresas',
            'view:pagos_ordenes_compra',
            'create:pagos_ordenes_compra',
            'edit:pagos_ordenes_compra',
            'delete:pagos_ordenes_compra',
            'view:estatus',
            'create:estatus',
            'edit:estatus',
            'delete:estatus',
            'view:logros_personal',
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
