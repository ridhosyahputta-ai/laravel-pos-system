<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\user;

class userseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $admin = User::create([
        'name' => 'admin',
        'email' => 'admin@gmail.com',
        'password' => bcrypt('admin123'),
      ]);
      $admin->assignRole('admin');

      $kasir = User::create([
        'name' => 'kasir',
        'email' => 'kasir@gmail.com',
        'password' => bcrypt('kasir123'),
      ]);
      $kasir->assignRole('kasir');
    }
}
