<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions list
        $permissions = [
            // Dashboard
            'view dashboard',
            
            // Penomoran
            'view number availability',
            'manage number availability',
            
            // Data Surat
            'view letter data',
            'manage letter data',
            
            // Tindak Lanjut / TTD
            'view signature lane',
            'manage signature lane',
            'update status qr',
            
            // Disposisi
            'view disposition lane',
            'manage disposition lane',
            'give disposition instruction',
            'update disposition progress',
            
            // Master Data
            'manage units',
            'manage categories',
            'manage number types',
            'manage users',
            'export master recap',
            'manage letter relations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 1. Super Admin (Full Access)
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // 2. Admin Operator
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'view dashboard',
            'view number availability',
            'manage number availability',
            'view letter data',
            'manage letter data',
            'view signature lane',
            'manage signature lane',
            'update status qr',
            'view disposition lane',
            'manage disposition lane',
            'update disposition progress',
            'manage letter relations',
        ]);

        // 3. Kasubbag
        $kasubbagRole = Role::firstOrCreate(['name' => 'kasubbag', 'guard_name' => 'web']);
        $kasubbagRole->syncPermissions([
            'view dashboard',
            'view number availability',
            'view letter data',
            'view signature lane',
            'manage signature lane',
            'update status qr',
            'view disposition lane',
            'manage disposition lane',
            'update disposition progress',
            'manage letter relations',
        ]);

        // 4. Sekjen (Pimpinan)
        $sekjenRole = Role::firstOrCreate(['name' => 'sekjen', 'guard_name' => 'web']);
        $sekjenRole->syncPermissions([
            'view dashboard',
            'view disposition lane',
            'give disposition instruction',
            'update disposition progress',

        ]);
    }
}
