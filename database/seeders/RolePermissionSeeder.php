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
            'lihat berita',
            'tambah berita',
            'hapus berita',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api', // gunakan guard API
            ]);
        }

        // Buat role dengan guard api
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $user  = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // Assign permission ke role
        $admin->givePermissionTo(Permission::all());
        $user->givePermissionTo('lihat berita');
    }
}
