<?php

namespace Database\Seeders;

use App\Models\Estatus;
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $personalRole = Role::firstOrCreate(['name' => 'personal']);

        $adminRole->syncPermissions($permissions);
        $userRole->syncPermissions(['view users']);

        $estatusActivo = Estatus::firstOrCreate(['estatus' => 'activo']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'nombre' => 'Admin',
                'apellido' => 'Root',
                'cedula' => 'A-0001',
                'telefono' => '0000000000',
                'porcentaje_comision' => 0,
                'id_rol' => $adminRole->id,
                'id_estatus' => $estatusActivo->id,
                'password' => Hash::make('password'),
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
                'id_rol' => $userRole->id,
                'id_estatus' => $estatusActivo->id,
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles([$userRole]);
    }
}
