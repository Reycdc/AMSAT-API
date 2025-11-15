<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat daftar permission
        $permissions = [
            // Content permissions
            'view content',
            'create content',
            'edit content',
            'delete content',
            'verify content',
            
            // User permissions
            'manage users',
            
            // Menu permissions
            'manage menus',
            
            // Category permissions
            'manage categories',
            
            // Banner permissions
            'manage banners',
            
            // Gallery permissions
            'manage galleries',
            
            // Membership permissions
            'manage memberships',
            
            // Comment permissions
            'manage comments',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api',
            ]);
        }

        // Buat roles dengan guard api
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'api']);
        $author = Role::firstOrCreate(['name' => 'author', 'guard_name' => 'api']);
        $redaktur = Role::firstOrCreate(['name' => 'redaktur', 'guard_name' => 'api']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // Assign permissions to roles
        // Admin has all permissions
        $admin->givePermissionTo(Permission::all());

        // Editor can manage content and categories
        $editor->givePermissionTo([
            'view content',
            'create content',
            'edit content',
            'delete content',
            'manage menus',
            'manage categories',
            'manage galleries',
            'manage comments',
        ]);

        // Author can create and edit their own content
        $author->givePermissionTo([
            'view content',
            'create content',
            'edit content',
            'delete content',
        ]);

        // Redaktur can verify content
        $redaktur->givePermissionTo([
            'view content',
            'verify content',
            'manage comments',
        ]);

        // User can only view content
        $user->givePermissionTo([
            'view content',
        ]);
    }
}