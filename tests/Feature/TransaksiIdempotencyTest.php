<?php

use App\Actions\CreateSale;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    $this->actingAs($admin);
    $this->saleAdmin = $admin;
    $this->saleItem = Produk::create([
        'nama' => 'Idempotent Runway', 'kode' => (string) Str::ulid(), 'harga' => 12500, 'stok' => 10,
    ]);
    $this->submission = [
        'idempotency_key' => Uuid::uuid4()->toString(),
        'produk_id' => [$this->saleItem->id], 'jumlah' => [2],
    ];
});

test('replaying a successful submission keeps the same sale details and stock', function () {
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    $sale = Transaksi::sole();
    $detail = TransaksiDetail::sole();
    $this->post(route('transaksi.store'), $this->submission)
        ->assertRedirect(route('transaksi.index'))->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Transaksi ini sudah tersimpan. Stok tidak dikurangi kembali.');
    expect(Transaksi::count())->toBe(1)->and(Transaksi::sole()->id)->toBe($sale->id)
        ->and(TransaksiDetail::count())->toBe(1)->and(TransaksiDetail::sole()->id)->toBe($detail->id)
        ->and($this->saleItem->fresh()->stok)->toBe(8)->and($sale->fresh()->total)->toBe('25000.00');
});

test('replay uses the committed outcome even after the price or stock changes', function () {
    $payload = array_replace($this->submission, ['jumlah' => [10]]);
    $this->postJson(route('transaksi.store'), $payload)->assertRedirect(route('transaksi.index'));
    $this->saleItem->update(['harga' => 9999]);
    $this->postJson(route('transaksi.store'), $payload)->assertRedirect(route('transaksi.index'));
    expect(Transaksi::count())->toBe(1)->and(Transaksi::sole()->total)->toBe('125000.00')
        ->and(TransaksiDetail::sole()->harga_satuan)->toBe('12500.00')->and($this->saleItem->fresh()->stok)->toBe(0);
});

test('different tokens permit separate sales of the same cart', function () {
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    $this->post(route('transaksi.store'), array_replace($this->submission, ['idempotency_key' => Uuid::uuid4()->toString()]))
        ->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(2)->and(TransaksiDetail::count())->toBe(2)
        ->and(Transaksi::pluck('idempotency_key')->unique()->count())->toBe(2)->and($this->saleItem->fresh()->stok)->toBe(6);
});

test('the same token cannot be repurposed for another cart', function () {
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    $this->postJson(route('transaksi.store'), array_replace($this->submission, ['jumlah' => [3]]))
        ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
    expect(Transaksi::count())->toBe(1)->and(TransaksiDetail::count())->toBe(1)->and($this->saleItem->fresh()->stok)->toBe(8);
});

test('one admin cannot replay another admins token', function () {
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    $other = User::factory()->create();
    $other->assignRole(Role::findOrCreate('admin', 'web'));
    $forged = $this->submission + ['user_id' => $this->saleAdmin->id, 'request_hash' => Transaksi::sole()->request_hash];
    $this->actingAs($other)->postJson(route('transaksi.store'), $forged)
        ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
    expect(Transaksi::count())->toBe(1)->and($this->saleItem->fresh()->stok)->toBe(8);
});

test('equivalent reordered and duplicate carts have the same fingerprint', function () {
    $other = Produk::create(['nama' => 'Other', 'kode' => 'IDEM-OTHER', 'harga' => 2500, 'stok' => 10]);
    $a = $this->saleItem->id;
    $payload = array_replace($this->submission, ['produk_id' => [$a, $other->id, (string) $a], 'jumlah' => [1, 2, 2]]);
    $this->post(route('transaksi.store'), $payload)->assertSessionHasNoErrors();
    $payload['produk_id'] = [(string) $other->id, (string) $a];
    $payload['jumlah'] = ['2', '3'];
    $payload['total'] = 1;
    $payload['harga'] = [1, 1];
    $this->post(route('transaksi.store'), $payload)->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(1)->and(Transaksi::sole()->total)->toBe('42500.00')
        ->and(TransaksiDetail::count())->toBe(2)->and($this->saleItem->fresh()->stok)->toBe(7)->and($other->fresh()->stok)->toBe(8);
});

test('uuid token casing cannot bypass idempotency', function () {
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    $this->post(route('transaksi.store'), array_replace($this->submission, ['idempotency_key' => strtoupper($this->submission['idempotency_key'])]))
        ->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(1)->and($this->saleItem->fresh()->stok)->toBe(8);
});

test('missing malformed or nested tokens are rejected without writes', function ($token) {
    $payload = array_replace($this->submission, ['idempotency_key' => $token]);
    $this->postJson(route('transaksi.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
    expect(Transaksi::count())->toBe(0)->and(TransaksiDetail::count())->toBe(0)->and($this->saleItem->fresh()->stok)->toBe(10);
})->with([null, '', 'not-a-uuid', 123, ['nested'], str_repeat('a', 100)]);

test('a validation failure leaves the token reusable for a corrected submission', function () {
    $this->postJson(route('transaksi.store'), array_replace($this->submission, ['jumlah' => [11]]))
        ->assertUnprocessable()->assertJsonValidationErrors('jumlah');
    expect(Transaksi::count())->toBe(0)->and(TransaksiDetail::count())->toBe(0)->and($this->saleItem->fresh()->stok)->toBe(10);
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(1)->and($this->saleItem->fresh()->stok)->toBe(8);
});

test('failed writes roll back the token claim so a retry can succeed exactly once', function () {
    DB::unprepared("CREATE TRIGGER fail_idempotent_detail BEFORE INSERT ON transaksi_details BEGIN SELECT RAISE(ABORT, 'isolated test failure'); END");
    try {
        $this->postJson(route('transaksi.store'), $this->submission)->assertUnprocessable()->assertJsonValidationErrors('transaksi');
        expect(Transaksi::count())->toBe(0)->and(TransaksiDetail::count())->toBe(0)->and($this->saleItem->fresh()->stok)->toBe(10);
    } finally {
        DB::unprepared('DROP TRIGGER fail_idempotent_detail');
    }
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(1)->and(TransaksiDetail::count())->toBe(1)->and($this->saleItem->fresh()->stok)->toBe(8);
});

test('a simulated winner between lookup and claim is resolved through the unique constraint', function ($conflictingCart) {
    // Deterministic interleaving on SQLite, NOT a native concurrent MariaDB test.
    $interleaved = false;
    $winner = null;
    $key = $this->submission['idempotency_key'];
    $productId = $this->saleItem->id;
    $userId = $this->saleAdmin->id;
    DB::listen(function ($query) use (&$interleaved, &$winner, $key, $productId, $userId, $conflictingCart) {
        if (! $interleaved && str_starts_with($query->sql, 'select * from "transaksis"') && in_array($key, $query->bindings, true)) {
            $interleaved = true;
            $winner = app(CreateSale::class)->handle([$productId], [$conflictingCart ? 3 : 2], $key, $userId);
        }
    });
    $response = $this->postJson(route('transaksi.store'), $this->submission);
    if ($conflictingCart) {
        $response->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
    } else {
        $response->assertRedirect(route('transaksi.index'));
    }
    expect($interleaved)->toBeTrue()->and(Transaksi::count())->toBe(1)->and(Transaksi::sole()->id)->toBe($winner->id)
        ->and(TransaksiDetail::count())->toBe(1)->and($this->saleItem->fresh()->stok)->toBe($conflictingCart ? 7 : 8);
})->with([false, true]);

test('each new form receives a token and validation redirects preserve it', function () {
    $first = $this->get(route('transaksi.create'))->assertOk();
    $second = $this->get(route('transaksi.create'))->assertOk();
    preg_match('/name="idempotency_key" value="([^"]+)"/', $first->getContent(), $a);
    preg_match('/name="idempotency_key" value="([^"]+)"/', $second->getContent(), $b);
    expect(Str::isUuid($a[1]))->toBeTrue()->and(Str::isUuid($b[1]))->toBeTrue()->and($a[1])->not->toBe($b[1]);
    $payload = array_replace($this->submission, ['jumlah' => [11]]);
    $this->from(route('transaksi.create'))->post(route('transaksi.store'), $payload)->assertSessionHasErrors('jumlah');
    $this->get(route('transaksi.create'))->assertOk()->assertSee('value="'.$payload['idempotency_key'].'"', false);
});

test('migration preserves historical sales and prevents discarding used idempotency history', function () {
    $migration = require database_path('migrations/2026_10_18_000002_add_idempotency_to_transaksis_table.php');
    $migration->down();
    $legacy = Transaksi::create(['kode_transaksi' => 'LEGACY-IDEMPOTENCY', 'total' => 100]);
    $migration->up();
    expect($legacy->fresh()->total)->toBe('100.00')->and($legacy->fresh()->idempotency_key)->toBeNull();
    // Multiple historical null keys remain valid under the unique index.
    Transaksi::create(['kode_transaksi' => 'LEGACY-IDEMPOTENCY-2', 'total' => 0]);
    $this->post(route('transaksi.store'), $this->submission)->assertSessionHasNoErrors();
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Cannot remove idempotency history');
    expect(Schema::hasColumn('transaksis', 'idempotency_key'))->toBeTrue()->and(Transaksi::count())->toBe(3);
});

test('an unapplied idempotency migration fails closed without changing business data', function () {
    $migration = require database_path('migrations/2026_10_18_000002_add_idempotency_to_transaksis_table.php');
    $migration->down();
    try {
        $this->postJson(route('transaksi.store'), $this->submission)->assertUnprocessable()->assertJsonValidationErrors('transaksi');
        expect(Transaksi::count())->toBe(0)->and(TransaksiDetail::count())->toBe(0)->and($this->saleItem->fresh()->stok)->toBe(10);
    } finally {
        $migration->up();
    }
});
