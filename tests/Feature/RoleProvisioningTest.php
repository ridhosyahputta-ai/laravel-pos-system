<?php

use App\Actions\EnsurePosRoles;
use App\Models\Cabang;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

function roleAuditAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin', 'web'));

    return $admin;
}

test('cashier list is safe and read only before cashier role exists', function () {
    $admin = roleAuditAdmin();
    expect(Role::where('name', 'kasir')->where('guard_name', 'web')->exists())->toBeFalse();
    $this->actingAs($admin)->get('/kasir')->assertOk()->assertSee('Belum ada akun Kasir.');
    expect(Role::count())->toBe(1)->and(User::count())->toBe(1);
});

test('role provisioning is idempotent and preserves existing admin and assignments', function () {
    $admin = roleAuditAdmin();
    $before = hash('sha256', serialize($admin->fresh()->getAttributes()));
    $role = Role::findByName('admin', 'web');
    $roleBefore = hash('sha256', serialize($role->fresh()->getAttributes()));
    $this->artisan('runway:provision-roles')->assertSuccessful();
    $this->artisan('runway:provision-roles')->assertSuccessful();
    expect(Role::count())->toBe(2)->and(Role::where('name', 'kasir')->where('guard_name', 'web')->count())->toBe(1)
        ->and(hash('sha256', serialize($admin->fresh()->getAttributes())))->toBe($before)->and(hash('sha256', serialize($role->fresh()->getAttributes())))->toBe($roleBefore)
        ->and($admin->fresh()->getRoleNames()->all())->toBe(['admin'])->and(User::count())->toBe(1);
    $this->actingAs($admin)->get('/kasir')->assertOk();
});

test('role provisioning supports fresh installation without creating users', function () {
    app(EnsurePosRoles::class)->handle();
    app(EnsurePosRoles::class)->handle();
    expect(Role::where('guard_name', 'web')->orderBy('name')->pluck('name')->all())->toBe(['admin', 'kasir'])
        ->and(User::count())->toBe(0);
});

test('role provisioning uses web guard independently from another guard', function () {
    Role::create(['name' => 'kasir', 'guard_name' => 'api']);
    app(EnsurePosRoles::class)->handle();
    expect(Role::where('name', 'kasir')->count())->toBe(2)
        ->and(Role::where('name', 'kasir')->where('guard_name', 'web')->count())->toBe(1);
});

test('fresh admin provisioning also prepares cashier role without cashier account', function () {
    foreach (['SEED_ADMIN_NAME' => 'Fixture Admin', 'SEED_ADMIN_EMAIL' => 'fixture-admin@example.test', 'SEED_ADMIN_PASSWORD' => 'Fixture-private-password'] as $key => $value) {
        $this->fixtureEnv[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    try {
        $this->seed(DatabaseSeeder::class);
        expect(User::count())->toBe(1)->and(User::sole()->hasRole('admin'))->toBeTrue()
            ->and(Role::where('name', 'kasir')->where('guard_name', 'web')->count())->toBe(1);
    } finally {
        foreach ($this->fixtureEnv as $key => [$old, $env, $server]) {
            $old === false ? putenv($key) : putenv($key.'='.$old);
            if ($env === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $env;
            }
            if ($server === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $server;
            }
        }
    }
});

test('admin cashier link uses normal navigation components on desktop and mobile', function () {
    $this->actingAs(roleAuditAdmin());
    $html = $this->get('/kasir')->assertOk()->getContent();
    $doc = new DOMDocument;
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    foreach (['desktop', 'mobile'] as $section) {
        $container = $xpath->query('//*[@data-navigation="'.$section.'"]')->item(0);
        expect($container)->not->toBeNull();
        $links = $xpath->query('.//a[@href="'.route('kasir.index').'"]', $container);
        expect($links->length)->toBe(1);
        if ($section === 'desktop') {
            expect($container->getAttribute('class'))->toContain('xl:flex')->toContain('whitespace-nowrap');
            expect($links->item(0)->getAttribute('class'))->toContain('inline-flex')->toContain('items-center')->toContain('border-b-2');
        }
    }
});

test('cashier has sales and profile navigation without admin cashier management', function () {
    app(EnsurePosRoles::class)->handle();
    $branch = Cabang::create(['nama_cabang' => 'Fixture']);
    $user = User::factory()->create(['cabang_id' => $branch->id]);
    $user->assignRole('kasir');
    $this->actingAs($user)->get('/transaksi')->assertOk()->assertDontSee('href="'.route('kasir.index').'"', false)
        ->assertDontSee('href="'.route('cabang.index').'"', false)->assertSee('Profile')->assertSee('Log Out');
});
