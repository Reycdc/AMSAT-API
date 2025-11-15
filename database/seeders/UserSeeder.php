<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@amsat.com',
            'password' => Hash::make('password'),
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Admin No. 1',
            'status' => 'active',
            'is_verified' => true,
        ]);
        $admin->assignRole('admin');

        // Create Editor User
        $editor = User::create([
            'username' => 'editor',
            'email' => 'editor@amsat.com',
            'password' => Hash::make('password'),
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Jl. Editor No. 2',
            'status' => 'active',
            'is_verified' => true,
        ]);
        $editor->assignRole('editor');

        // Create Author User
        $author = User::create([
            'username' => 'author',
            'email' => 'author@amsat.com',
            'password' => Hash::make('password'),
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Author No. 3',
            'status' => 'active',
            'is_verified' => true,
        ]);
        $author->assignRole('author');

        // Create Redaktur User
        $redaktur = User::create([
            'username' => 'redaktur',
            'email' => 'redaktur@amsat.com',
            'password' => Hash::make('password'),
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Jl. Redaktur No. 4',
            'status' => 'active',
            'is_verified' => true,
        ]);
        $redaktur->assignRole('redaktur');

        // Create Regular User
        $user = User::create([
            'username' => 'user',
            'email' => 'user@amsat.com',
            'password' => Hash::make('password'),
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. User No. 5',
            'status' => 'active',
            'is_verified' => true,
        ]);
        $user->assignRole('user');
    }
}