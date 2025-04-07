<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions if they don't exist
        $permissions = [
            'add_products',
            'edit_products',
            'delete_products',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create or update roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions($permissions);

        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $employee->syncPermissions($permissions);

        Role::firstOrCreate(['name' => 'Customer']);
    }
} 