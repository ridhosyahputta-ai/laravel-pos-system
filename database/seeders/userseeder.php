<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class userseeder extends Seeder
{
    public function run(): void
    {
        $accounts = [];
        foreach (['admin' => 'SEED_ADMIN', 'kasir' => 'SEED_CASHIER'] as $role => $prefix) {
            $name = env($prefix.'_NAME');
            $email = env($prefix.'_EMAIL');
            $password = env($prefix.'_PASSWORD');

            if (! is_string($name) || trim($name) === ''
                || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)
                || ! is_string($password) || $password === '') {
                throw new \RuntimeException('Set all private initial-account environment values before explicitly running this seeder on a fresh database.');
            }

            $accounts[] = compact('role', 'name', 'email', 'password');
        }

        DB::transaction(function () use ($accounts) {
            foreach ($accounts as $account) {
                $user = User::create([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $account['password'],
                ]);
                $user->assignRole($account['role']);
            }
        });
    }
}
