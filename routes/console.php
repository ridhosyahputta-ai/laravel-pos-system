<?php

use App\Actions\EnsurePosRoles;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('runway:provision-roles', function (EnsurePosRoles $provision) {
    $roles = $provision->handle();
    $created = count(array_filter($roles, fn ($role) => $role->wasRecentlyCreated));
    $this->info("POS roles ready for web guard; created: {$created}. No accounts changed.");
})->purpose('Provision missing Admin and Kasir roles without creating accounts');
