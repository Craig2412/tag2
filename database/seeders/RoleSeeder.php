<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        $adminRole->syncPermissions($permissions);
        $userRole->syncPermissions(['view users']);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => $this->adminPassword(),
            ]
        );

        $adminUser->assignRole($adminRole);
    }

    private function adminPassword(): string
    {
        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new \RuntimeException('Set ADMIN_PASSWORD in .env before running RoleSeeder.');
        }

        return Hash::make($password);
    }
}

// database/seeders/RoleSeeder.php - Crea roles y permisos base, asigna permisos por rol y crea un admin inicial con su rol.
