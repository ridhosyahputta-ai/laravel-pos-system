<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        
        $user = \App\Models\User::create([
            'name' => 'Admin Daffa',
            'email' => 'admin@test.com',
            'password' => bcrypt('12345678'),
        ]);
    
        $user->assignRole($role);
    }
}
