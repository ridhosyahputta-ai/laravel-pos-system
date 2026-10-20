<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class EnsurePosRoles
{
    /** Create missing roles only; never update roles or assign users. */
    public function handle(): array
    {
        return DB::transaction(function () {
            $roles = [];
            foreach (['admin', 'kasir'] as $name) {
                $roles[$name] = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }

            return $roles;
        });
    }
}
