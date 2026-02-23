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
            'view users',
            'edit users',
            'delete users',
            'view tipos proveedores',
            'create tipos proveedores',
            'edit tipos proveedores',
            'delete tipos proveedores',
            'view proveedores',
            'create proveedores',
            'edit proveedores',
            'delete proveedores',
            'view tipo servicio',
            'create tipo servicio',
            'edit tipo servicio',
            'delete tipo servicio',
            'view tasas cambio',
            'create tasas cambio',
            'edit tasas cambio',
            'delete tasas cambio',
            'view servicios',
            'create servicios',
            'edit servicios',
            'delete servicios',
            'view origenes',
            'create origenes',
            'edit origenes',
            'delete origenes',
            'view atenciones',
            'create atenciones',
            'edit atenciones',
            'delete atenciones',
            'view atenciones personal',
            'create atenciones personal',
            'edit atenciones personal',
            'delete atenciones personal',
            'view cotizaciones',
            'create cotizaciones',
            'edit cotizaciones',
            'delete cotizaciones',
            'view tipos cotizaciones',
            'create tipos cotizaciones',
            'edit tipos cotizaciones',
            'delete tipos cotizaciones',
            'view servicios cotizaciones',
            'create servicios cotizaciones',
            'edit servicios cotizaciones',
            'delete servicios cotizaciones',
            'view metodos pago',
            'create metodos pago',
            'edit metodos pago',
            'delete metodos pago',
            'view pagos',
            'create pagos',
            'edit pagos',
            'delete pagos',
            'view tipos contribuyentes',
            'create tipos contribuyentes',
            'edit tipos contribuyentes',
            'delete tipos contribuyentes',
            'view empresas',
            'create empresas',
            'edit empresas',
            'delete empresas',
            'view personal empresas',
            'create personal empresas',
            'edit personal empresas',
            'delete personal empresas',
            'view pagos proveedores',
            'create pagos proveedores',
            'edit pagos proveedores',
            'delete pagos proveedores',
            'view cuentas proveedores',
            'create cuentas proveedores',
            'edit cuentas proveedores',
            'delete cuentas proveedores',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $personalRole = Role::firstOrCreate(['name' => 'personal']);
        $clienteRole = Role::firstOrCreate(['name' => 'cliente']);

        $adminRole->syncPermissions($permissions);
        $userRole->syncPermissions(['view users']);
        $personalRole->syncPermissions([]);
        $clienteRole->syncPermissions([]);

        $estatusActivo = Estatus::firstOrCreate(['estatus' => 'activo']);

        $tipoContribuyenteNormal = TipoContribuyente::firstOrCreate(
            ['tipo_contribuyente' => 'Normal'],
            ['porcentaje_iva' => 16]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
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
            ]
        );

        $admin->syncRoles([$adminRole]);

        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
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
            ]
        );

        $user->syncRoles([$userRole]);

        $personal = User::firstOrCreate(
            ['email' => 'personal@example.com'],
            [
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
