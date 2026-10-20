<?php

namespace Database\Seeders;

use App\Actions\EnsurePosRoles;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $name = env('SEED_ADMIN_NAME');
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (! is_string($name) || trim($name) === ''
            || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! is_string($password) || $password === '') {
            throw new \RuntimeException('Set private SEED_ADMIN_NAME, SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD before explicitly seeding a fresh database.');
        }

        DB::transaction(function () use ($name, $email, $password) {
            $role = app(EnsurePosRoles::class)->handle()['admin'];
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->assignRole($role);
        });
    }
}
