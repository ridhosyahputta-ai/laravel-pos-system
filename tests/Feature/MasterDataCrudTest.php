<?php

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Petugas;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

function masterDataCases(): array
{
    return [
        'supplier' => [Supplier::class, 'suppliers', 'nama_supplier', ['nama_supplier' => 'Supplier Runway', 'kontak' => '08123456789', 'alamat' => 'Jalan Supplier']],
        'pelanggan' => [Pelanggan::class, 'pelanggans', 'nama', ['nama' => 'Pelanggan Test', 'no_hp' => '08123456789', 'alamat' => 'Jalan Pelanggan']],
        'cabang' => [Cabang::class, 'cabangs', 'nama_cabang', ['nama_cabang' => 'Cabang Test', 'lokasi' => 'Jakarta']],
        'petugas' => [Petugas::class, 'petugas', 'nama', ['nama' => 'Petugas Test', 'username' => 'petugas_test', 'role' => 'Kasir']],
    ];
}

dataset('master data modules', array_keys(masterDataCases()));

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    $this->actingAs($admin);
});

test('admin can create edit update and delete the exact requested master record', function ($module) {
    [$model, $table, $name, $payload] = masterDataCases()[$module];
    $this->get(route($module.'.create'))->assertOk()->assertSee('Run')->assertSee('way');
    $this->post(route($module.'.store'), $payload + ['id' => 999999, 'email' => 'ignored@example.invalid'])
        ->assertSessionHasNoErrors()->assertRedirect(route($module.'.index'));
    $record = $model::sole();
    expect($record->id)->not->toBe(999999);
    $otherPayload = array_replace($payload, [$name => 'Other record']);
    if ($module === 'petugas') {
        $otherPayload['username'] = 'other_petugas';
    }
    $other = $model::create($otherPayload);
    $this->get(route($module.'.index'))->assertOk()->assertSee($payload[$name])
        ->assertSee(route($module.'.edit', $record), false)
        ->assertSee(route($module.'.destroy', $record), false);
    $this->get(route($module.'.edit', $record))->assertOk()
        ->assertViewHas($module, fn ($bound) => $bound->is($record))
        ->assertSee(route($module.'.update', $record), false);

    $updated = array_replace($payload, [$name => 'Updated record']);
    $this->put(route($module.'.update', $record), $updated)
        ->assertSessionHasNoErrors()->assertRedirect(route($module.'.index'));
    $this->assertDatabaseHas($table, ['id' => $record->id] + $updated);
    $this->assertDatabaseHas($table, ['id' => $other->id] + $otherPayload);
    $this->delete(route($module.'.destroy', $record))->assertRedirect(route($module.'.index'))->assertSessionHas('success');
    $this->assertDatabaseMissing($table, ['id' => $record->id]);
    $this->assertDatabaseHas($table, ['id' => $other->id] + $otherPayload);
    expect($model::count())->toBe(1);
})->with('master data modules');

test('optional master data fields accept blank values and are stored as null', function ($module) {
    [$model, $table, $name, $payload] = masterDataCases()[$module];
    foreach ($payload as $field => &$value) {
        if ($field !== $name) {
            $value = '';
        }
    }
    unset($value);
    $this->post(route($module.'.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route($module.'.index'));
    $record = $model::sole();
    $this->put(route($module.'.update', $record), $payload)->assertSessionHasNoErrors();
    foreach ($payload as $field => $value) {
        if ($field !== $name) {
            expect($record->fresh()->getAttribute($field))->toBeNull();
        }
    }
})->with(['supplier', 'pelanggan', 'cabang']);

dataset('invalid master data input', function () {
    $invalid = [
        'supplier' => ['nama_supplier' => ['', str_repeat('n', 256), ['invalid']], 'kontak' => [str_repeat('1', 256), ['invalid']], 'alamat' => [str_repeat('x', 2001), ['invalid']]],
        'pelanggan' => ['nama' => ['', str_repeat('n', 256), ['invalid']], 'no_hp' => [str_repeat('1', 21), ['invalid']], 'alamat' => [str_repeat('x', 2001), ['invalid']]],
        'cabang' => ['nama_cabang' => ['', str_repeat('n', 256), ['invalid']], 'lokasi' => [str_repeat('x', 256), ['invalid']]],
        'petugas' => ['nama' => ['', str_repeat('n', 256), ['invalid']], 'username' => ['', str_repeat('u', 256), ['invalid']], 'role' => ['', 'superadmin', ['Admin']]],
    ];
    $cases = [];
    foreach ($invalid as $module => $fields) {
        foreach ($fields as $field => $values) {
            foreach ($values as $index => $value) {
                $cases[$module.' '.$field.' '.$index] = [$module, $field, $value];
            }
        }
    }
    return $cases;
});

test('invalid input is rejected on both create and update without changing stored data', function ($module, $field, $value) {
    [$model, $table, $name, $payload] = masterDataCases()[$module];
    $record = $model::create($payload);
    $before = $record->fresh()->getAttributes();
    $invalid = array_replace($payload, [$field => $value]);
    $this->post(route($module.'.store'), $invalid)->assertSessionHasErrors($field);
    $this->put(route($module.'.update', $record), $invalid)->assertSessionHasErrors($field);
    expect($model::count())->toBe(1)->and($record->fresh()->getAttributes())->toBe($before);
})->with('invalid master data input');

test('required field errors are clear and displayed in the form', function ($module) {
    [, , $name] = masterDataCases()[$module];
    $this->from(route($module.'.create'))->post(route($module.'.store'), [])
        ->assertRedirect(route($module.'.create'))->assertSessionHasErrors($name);
    $this->get(route($module.'.create'))->assertOk()->assertSee('wajib diisi.');
})->with('master data modules');

test('maximum permitted field lengths are accepted', function ($module) {
    [$model, , $name, $payload] = masterDataCases()[$module];
    $payload[$name] = str_repeat('n', 255);
    foreach ($payload as $field => &$value) {
        $value = match ($field) {
            'alamat' => str_repeat('中', 2000),
            'kontak', 'lokasi', 'username' => str_repeat('x', 255),
            'no_hp' => '0'.str_repeat('1', 19),
            default => $value,
        };
    }
    unset($value);
    $this->post(route($module.'.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route($module.'.index'));
    $record = $model::sole();
    foreach ($payload as $field => $value) {
        expect($record->getAttribute($field))->toBe($value);
    }
})->with('master data modules');

test('petugas route binding and username uniqueness preserve the correct record', function () {
    $first = Petugas::create(['nama' => 'First', 'username' => 'first', 'role' => 'Kasir']);
    $second = Petugas::create(['nama' => 'Second', 'username' => 'second', 'role' => 'Admin']);
    expect(Route::getRoutes()->getByName('petugas.edit')->parameterNames())->toBe(['petugas']);
    $this->post(route('petugas.store'), ['nama' => 'Duplicate', 'username' => 'first', 'role' => 'Kasir'])
        ->assertSessionHasErrors(['username' => 'Username sudah digunakan petugas lain.']);
    $this->put(route('petugas.update', $second), ['nama' => 'Changed', 'username' => 'first', 'role' => 'Admin'])
        ->assertSessionHasErrors('username');
    $this->put(route('petugas.update', $second), ['nama' => 'Changed', 'username' => 'second', 'role' => 'Kasir'])
        ->assertSessionHasNoErrors()->assertRedirect(route('petugas.index'));
    expect($first->fresh()->nama)->toBe('First')->and($second->fresh()->nama)->toBe('Changed');
});

test('deleting master data does not remove products transactions or authentication accounts', function ($module) {
    [$model, $table, , $payload] = masterDataCases()[$module];
    $record = $model::create($payload);
    $produk = Produk::create(['nama' => 'Kept product', 'kode' => 'KEEP', 'harga' => 10000, 'stok' => 5, 'supplier_id' => $module === 'supplier' ? $record->id : null]);
    $transaksi = Transaksi::create(['kode_transaksi' => 'TEST-KEEP', 'total' => 10000]);
    $detail = $transaksi->detail()->create(['produk_id' => $produk->id, 'jumlah' => 1, 'harga_satuan' => 10000, 'subtotal' => 10000]);
    $before = [$produk->fresh()->getAttributes(), $transaksi->fresh()->getAttributes(), $detail->fresh()->getAttributes()];
    $accountsBefore = hash('sha256', DB::table('users')->orderBy('id')->get()->toJson());
    $rolesBefore = DB::table('model_has_roles')->orderBy('model_id')->get()->toJson();

    $this->delete(route($module.'.destroy', $record))->assertRedirect(route($module.'.index'));
    $this->assertDatabaseMissing($table, ['id' => $record->id]);
    $before[0]['supplier_id'] = null;
    expect([$produk->fresh()->getAttributes(), $transaksi->fresh()->getAttributes(), $detail->fresh()->getAttributes()])->toBe($before)
        ->and(hash('sha256', DB::table('users')->orderBy('id')->get()->toJson()))->toBe($accountsBefore)
        ->and(DB::table('model_has_roles')->orderBy('model_id')->get()->toJson())->toBe($rolesBefore);
})->with('master data modules');

test('editing petugas role does not create or change an authentication account', function () {
    $petugas = Petugas::create(['nama' => 'Petugas', 'username' => 'staff', 'role' => 'Kasir']);
    $accountsBefore = hash('sha256', DB::table('users')->get()->toJson());
    $rolesBefore = DB::table('model_has_roles')->get()->toJson();
    $this->put(route('petugas.update', $petugas), ['nama' => 'Petugas', 'username' => 'staff', 'role' => 'Admin'])
        ->assertSessionHasNoErrors()->assertRedirect(route('petugas.index'));
    expect($petugas->fresh()->role)->toBe('Admin')
        ->and(hash('sha256', DB::table('users')->get()->toJson()))->toBe($accountsBefore)
        ->and(DB::table('model_has_roles')->get()->toJson())->toBe($rolesBefore);
});

test('missing records return 404 and unimplemented detail routes are absent', function ($module) {
    expect(Route::has($module.'.show'))->toBeFalse();
    $this->get(route($module.'.edit', 999999))->assertNotFound();
    $this->put(route($module.'.update', 999999), [])->assertNotFound();
    $this->delete(route($module.'.destroy', 999999))->assertNotFound();
})->with('master data modules');

test('guests and users without admin role cannot mutate master data', function ($module) {
    [$model, $table, , $payload] = masterDataCases()[$module];
    $record = $model::create($payload);
    $before = $record->fresh()->getAttributes();
    $this->app['auth']->forgetGuards();
    $this->get(route($module.'.index'))->assertRedirect(route('login'));
    $this->post(route($module.'.store'), $payload)->assertRedirect(route('login'));
    $this->put(route($module.'.update', $record), $payload)->assertRedirect(route('login'));
    $this->delete(route($module.'.destroy', $record))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create());
    $this->get(route($module.'.index'))->assertForbidden();
    $this->post(route($module.'.store'), $payload)->assertForbidden();
    $this->put(route($module.'.update', $record), $payload)->assertForbidden();
    $this->delete(route($module.'.destroy', $record))->assertForbidden();
    expect($model::count())->toBe(1)->and($record->fresh()->getAttributes())->toBe($before);
})->with('master data modules');
