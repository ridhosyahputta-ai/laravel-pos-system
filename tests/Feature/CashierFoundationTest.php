<?php

use App\Models\Cabang;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

function foundationUser(string $role = 'kasir', array $attributes = []): User
{
    $branch = $role === 'kasir' ? Cabang::create(['nama_cabang' => 'Runway Test']) : null;
    $user = User::factory()->create($attributes + ['cabang_id' => $branch?->id]);
    $user->assignRole(Role::findOrCreate($role, 'web'));

    return $user;
}
function foundationCart(): array
{
    $p = Produk::create(['nama' => 'Test', 'kode' => (string) Str::uuid(), 'harga' => 12000, 'stok' => 5]);

    return ['idempotency_key' => (string) Str::uuid(), 'produk_id' => [$p->id], 'jumlah' => [2]];
}
test('cashier can login and is redirected away from stale admin intended URL', function () {
    $cashier = foundationUser();
    $this->withSession(['url.intended' => '/dashboard'])->post('/login', ['email' => $cashier->email, 'password' => 'password'])->assertRedirect(route('transaksi.index'));
    $this->assertAuthenticatedAs($cashier);
});
test('inactive cashier cannot login', function () {
    $cashier = foundationUser(attributes: ['is_active' => false]);
    $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});
test('existing session is rejected after deactivation', function () {
    $cashier = foundationUser();
    $this->actingAs($cashier);
    User::whereKey($cashier->id)->update(['is_active' => false]);
    $this->get('/transaksi')->assertRedirect(route('login'));
    $this->assertGuest();
});
test('roleless users cannot login or access sales', function () {
    $user = User::factory()->create();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->actingAs($user)->get('/transaksi')->assertForbidden();
});
test('cashier without a branch cannot access any sale endpoint', function () {
    $cashier = foundationUser();
    $cashier->update(['cabang_id' => null]);
    $this->actingAs($cashier)->get('/transaksi/create')->assertForbidden();
    $this->post('/transaksi', foundationCart())->assertForbidden();
});
test('cashier cannot access admin endpoints directly', function (string $url) {
    $this->actingAs(foundationUser())->get($url)->assertForbidden();
})->with(['/dashboard', '/produk', '/kategori', '/supplier', '/pelanggan', '/cabang', '/petugas', '/kasir', '/kasir/create', '/produk/export/pdf']);
test('admin creates a hashed cashier with a backend role and required branch', function () {
    $admin = foundationUser('admin');
    $b = Cabang::create(['nama_cabang' => 'Utama']);
    $this->actingAs($admin)->post('/kasir', ['name' => 'Cashier', 'email' => 'cashier@example.test', 'password' => 'Strong-test-123', 'password_confirmation' => 'Strong-test-123', 'cabang_id' => $b->id])->assertSessionHasNoErrors()->assertRedirect(route('kasir.index'));
    $c = User::where('email', 'cashier@example.test')->sole();
    expect(Hash::check('Strong-test-123', $c->password))->toBeTrue()->and($c->hasRole('kasir'))->toBeTrue()->and($c->hasRole('admin'))->toBeFalse()->and($c->cabang_id)->toBe($b->id)->and($c->is_active)->toBeTrue();
    $this->get('/kasir')->assertOk()->assertSee('Cashier');
    $this->get(route('kasir.edit', $c))->assertOk();
});
test('cashier creation rejects privilege escalation and invalid inputs', function (array $overrides, string $error) {
    $admin = foundationUser('admin');
    $b = Cabang::create(['nama_cabang' => 'Utama']);
    $data = ['name' => 'Cashier', 'email' => 'cashier@example.test', 'password' => 'Strong-test-123', 'password_confirmation' => 'Strong-test-123', 'cabang_id' => $b->id];
    $this->actingAs($admin)->post('/kasir', array_replace($data, $overrides))->assertSessionHasErrors($error);
    expect(User::count())->toBe(1);
})->with([[['role' => 'admin'], 'role'], [['cabang_id' => null], 'cabang_id'], [['cabang_id' => 9999], 'cabang_id'], [['password' => 'short'], 'password'], [['email' => 'invalid'], 'email']]);
test('admin can edit and deactivate a cashier without deleting history or changing admin', function () {
    $admin = foundationUser('admin');
    $cashier = foundationUser();
    $b = Cabang::create(['nama_cabang' => 'Baru']);
    $this->actingAs($admin)->put(route('kasir.update', $cashier), ['name' => 'Edited', 'email' => $cashier->email, 'cabang_id' => $b->id, 'password' => ''])->assertSessionHasNoErrors();
    expect($cashier->fresh()->name)->toBe('Edited')->and($cashier->fresh()->cabang_id)->toBe($b->id)->and(Hash::check('password', $cashier->fresh()->password))->toBeTrue();
    $this->patch(route('kasir.deactivate', $cashier))->assertRedirect(route('kasir.index'));
    expect($cashier->fresh()->is_active)->toBeFalse()->and($admin->fresh()->is_active)->toBeTrue();
    $this->get(route('kasir.edit', $admin))->assertNotFound();
    $this->put(route('kasir.update', $admin), [])->assertNotFound();
    $this->patch(route('kasir.deactivate', $admin))->assertNotFound();
    $this->delete('/kasir/'.$cashier->id)->assertStatus(405);
});
test('cashier sale ignores browser ownership and preserves idempotency and stock', function () {
    $cashier = foundationUser();
    $other = foundationUser();
    $cart = foundationCart();
    $this->actingAs($cashier)->post('/transaksi', $cart + ['user_id' => $other->id, 'cabang_id' => $other->cabang_id, 'role' => 'admin'])->assertSessionHasNoErrors();
    $sale = Transaksi::sole();
    expect($sale->user_id)->toBe($cashier->id)->and($sale->cabang_id)->toBe($cashier->cabang_id)->and($cashier->hasRole('admin'))->toBeFalse();
    $this->post('/transaksi', $cart)->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(1)->and(Produk::find($cart['produk_id'][0])->stok)->toBe(3);
    $this->get(route('transaksi.show', $sale))->assertOk();
    $this->actingAs($other)->get(route('transaksi.show', $sale))->assertForbidden();
    $this->get('/transaksi')->assertDontSee($sale->kode_transaksi);
    $this->post('/transaksi', $cart)->assertSessionHasErrors('idempotency_key');
    $this->actingAs(foundationUser('admin'))->get(route('transaksi.show', $sale))->assertOk();
});
test('legacy transactions remain admin only and compatible with old token replay', function () {
    $admin = foundationUser('admin');
    $cart = foundationCart();
    $this->actingAs($admin)->post('/transaksi', $cart)->assertSessionHasNoErrors();
    $sale = Transaksi::sole();
    $sale->update(['user_id' => null, 'cabang_id' => null]);
    $this->post('/transaksi', $cart)->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(1)->and($sale->fresh()->user_id)->toBeNull()->and(Produk::find($cart['produk_id'][0])->stok)->toBe(3);
    $this->get(route('transaksi.show', $sale))->assertOk();
    $this->actingAs(foundationUser())->get(route('transaksi.show', $sale))->assertForbidden();
});
test('branch deletion is blocked for linked accounts and historical sales', function () {
    $admin = foundationUser('admin');
    $cashier = foundationUser();
    $this->actingAs($admin)->delete('/cabang/'.$cashier->cabang_id)->assertSessionHasErrors('cabang');
    expect(Cabang::find($cashier->cabang_id))->not->toBeNull();
});
test('profile cannot modify role branch or status and navigation is role aware', function () {
    $cashier = foundationUser();
    $original = $cashier->cabang_id;
    $this->actingAs($cashier)->patch('/profile', ['name' => $cashier->name, 'email' => $cashier->email, 'role' => 'admin', 'cabang_id' => null, 'is_active' => false])->assertSessionHasNoErrors();
    expect($cashier->fresh()->cabang_id)->toBe($original)->and($cashier->fresh()->is_active)->toBeTrue()->and($cashier->fresh()->hasRole('admin'))->toBeFalse();
    $this->get('/transaksi/create')->assertOk()->assertDontSee('href="'.route('produk.index').'"', false);
});
test('empty branches show setup instructions', function () {
    $this->actingAs(foundationUser('admin'))->get('/kasir/create')->assertOk()->assertSee('Buat Cabang terlebih dahulu');
});

test('cashier cannot submit mutations to admin endpoints', function (string $method, string $url) {
    $this->actingAs(foundationUser())->call($method, $url, [])->assertForbidden();
})->with([['POST', '/kasir'], ['POST', '/produk'], ['POST', '/kategori'], ['POST', '/supplier'], ['POST', '/pelanggan'], ['POST', '/cabang'], ['POST', '/petugas']]);
test('cashier endpoints reject privilege injection during editing', function () {
    $admin = foundationUser('admin');
    $c = foundationUser();
    $this->actingAs($admin)->put(route('kasir.update', $c), ['name' => 'Bad', 'email' => $c->email, 'cabang_id' => $c->cabang_id, 'role' => 'admin'])->assertSessionHasErrors('role');
    expect($c->fresh()->name)->toBe($c->name)->and($c->fresh()->hasRole('admin'))->toBeFalse();
});
test('foundation migration preserves legacy rows and defaults admin active', function () {
    $migration = require database_path('migrations/2026_10_19_000001_add_cashier_foundation.php');
    $migration->down();
    $id = DB::table('users')->insertGetId(['name' => 'Legacy Admin', 'email' => 'legacy@example.test', 'password' => Hash::make('legacy-test-password')]);
    $sale = DB::table('transaksis')->insertGetId(['kode_transaksi' => 'LEGACY', 'total' => 12000]);
    $before = (array) DB::table('users')->find($id);
    $this->actingAs(User::findOrFail($id))->get('/profile')->assertStatus(503);
    $this->assertAuthenticated();
    $migration->up();
    $after = (array) DB::table('users')->find($id);
    foreach ($before as $column => $value) {
        expect($after[$column])->toBe($value);
    }
    expect((bool) $after['is_active'])->toBeTrue()->and($after['cabang_id'])->toBeNull();
    $row = DB::table('transaksis')->find($sale);
    expect($row->user_id)->toBeNull()->and($row->cabang_id)->toBeNull()->and((int) $row->total)->toBe(12000);
});

test('cashier keeps profile and logout navigation on mobile', function () {
    $this->actingAs(foundationUser())->get('/transaksi')
        ->assertOk()->assertSee('Profile')->assertSee('Log Out');
    $this->get('/profile')->assertOk();
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

test('authenticated cashier visiting login is directed to sales', function () {
    $this->actingAs(foundationUser())->get('/login')->assertRedirect(route('transaksi.index'));
});
test('reassignment does not rewrite transaction branch history', function () {
    $cashier = foundationUser();
    $oldBranch = $cashier->cabang_id;
    $this->actingAs($cashier)->post('/transaksi', foundationCart())->assertSessionHasNoErrors();
    $sale = Transaksi::sole();
    $newBranch = Cabang::create(['nama_cabang' => 'Baru']);
    $cashier->update(['cabang_id' => $newBranch->id]);
    expect($sale->fresh()->cabang_id)->toBe($oldBranch);
    $this->get(route('transaksi.show', $sale))->assertOk();
    $cashier->update(['cabang_id' => null]);
    $this->actingAs(foundationUser('admin'))->delete('/cabang/'.$oldBranch)->assertSessionHasErrors('cabang');
});

test('empty prohibited fields cannot override backend activation or role', function () {
    $a = foundationUser('admin');
    $b = Cabang::create(['nama_cabang' => 'Utama']);
    $this->actingAs($a)->post('/kasir', ['name' => 'Cashier', 'email' => 'null-fields@example.test', 'password' => 'Strong-test-123', 'password_confirmation' => 'Strong-test-123', 'cabang_id' => $b->id, 'is_active' => null, 'role' => null, 'user_id' => null])->assertSessionHasNoErrors();
    $u = User::where('email', 'null-fields@example.test')->sole();
    expect($u->is_active)->toBeTrue()->and($u->hasRole('kasir'))->toBeTrue()->and($u->hasRole('admin'))->toBeFalse();
});
