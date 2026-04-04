<?php

namespace Database\Seeders;

use App\Models\Estatus;
use App\Models\TipoContribuyente;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view:users',
            'edit:users',
            'delete:users',
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
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);
        $personalRole = Role::create(['name' => 'personal']);
        $clienteRole = Role::create(['name' => 'cliente']);

        $adminRole->syncPermissions($permissions);
        $userRole->syncPermissions(['view:users']);
        $personalRole->syncPermissions([]);
        $clienteRole->syncPermissions([]);

        $estatusActivo = Estatus::firstOrCreate(['estatus' => 'activo']);

        $tipoContribuyenteNormal = TipoContribuyente::create([
            'tipo_contribuyente' => 'Normal',
            'porcentaje_iva' => 16
        ]);

        $admin = User::create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'nombre' => 'Admin',
            'apellido' => 'Root',
            'cedula' => 'A-0001',
            'telefono' => '0000000000',
            'porcentaje_comision' => 0,
            'id_tipo_contribuyente' => $tipoContribuyenteNormal->id,
            'id_rol' => $adminRole->id,
            'id_estatus' => $estatusActivo->id,
            'password' => Hash::make('password'),
            'correo_institucional' => null,
        ]);

        $admin->syncRoles([$adminRole]);

        $user = User::create([
            'email' => 'user@example.com',
            'name' => 'User',
            'nombre' => 'User',
            'apellido' => 'Default',
            'cedula' => 'U-0001',
            'telefono' => '0000000000',
            'porcentaje_comision' => 0,
            'id_tipo_contribuyente' => $tipoContribuyenteNormal->id,
            'id_rol' => $userRole->id,
            'id_estatus' => $estatusActivo->id,
            'password' => Hash::make('password'),
            'correo_institucional' => null,
        ]);

        $user->syncRoles([$userRole]);

        $personal = User::create([
            'email' => 'personal@example.com',
                'name' => 'Personal',
                'nombre' => 'Personal',
                'apellido' => 'Staff',
                'cedula' => 'P-0001',
                'telefono' => '0000000000',
                'porcentaje_comision' => 0,
                'id_tipo_contribuyente' => $tipoContribuyenteNormal->id,
                'id_rol' => $personalRole->id,
                'id_estatus' => $estatusActivo->id,
                'password' => Hash::make('password'),
                'correo_institucional' => null,
            ]
        );

        $personal->syncRoles([$personalRole]);

        $cliente = User::firstOrCreate(
            ['email' => 'cliente@example.com'],
            [
                'name' => 'Cliente',
                'nombre' => 'Cliente',
                'apellido' => 'Demo',
                'cedula' => 'C-0001',
                'telefono' => '0000000000',
                'porcentaje_comision' => 0,
                'id_tipo_contribuyente' => $tipoContribuyenteNormal->id,
                'id_rol' => $clienteRole->id,
                'id_estatus' => $estatusActivo->id,
                'password' => Hash::make('password'),
                'correo_institucional' => null,
            ]
        );

        $cliente->syncRoles([$clienteRole]);
    }
}
