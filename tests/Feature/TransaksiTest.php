<?php

use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\User;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    $this->actingAs($admin);
});

afterEach(function () {
    Str::createUuidsNormally();
    $this->travelBack();
});

function saleProduct(array $overrides = []): Produk
{
    return Produk::create(array_replace([
        'nama' => 'Produk Runway', 'kode' => (string) Str::ulid(),
        'harga' => 12500, 'stok' => 10,
    ], $overrides));
}

function assertNoSale(): void
{
    expect(Transaksi::count())->toBe(0)->and(TransaksiDetail::count())->toBe(0);
}

test('admin can sell one product using the database price and persist price snapshots', function () {
    $product = saleProduct();
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [3]])
        ->assertSessionHasNoErrors()->assertRedirect(route('transaksi.index'))->assertSessionHas('success');
    $sale = Transaksi::sole();
    $line = $sale->detail()->sole();
    expect($sale->total)->toBe('37500.00')->and($sale->kode_transaksi)->toStartWith('TRX-')
        ->and($line->produk_id)->toBe($product->id)->and($line->jumlah)->toBe(3)
        ->and($line->harga_satuan)->toBe('12500.00')->and($line->subtotal)->toBe('37500.00')
        ->and($product->fresh()->stok)->toBe(7);

    $product->update(['harga' => 15000]);
    expect($line->fresh()->harga_satuan)->toBe('12500.00')->and($sale->fresh()->total)->toBe('37500.00');
    $this->get(route('transaksi.show', $sale))->assertOk()->assertSee('37.500')->assertSee('12.500');
});

test('multiple products use their respective quantities and leave unrelated stock intact', function () {
    $a = saleProduct(['harga' => 1000]);
    $b = saleProduct(['harga' => 2500]);
    $other = saleProduct();
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$b->id, $a->id], 'jumlah' => [3, 2]])
        ->assertSessionHasNoErrors()->assertRedirect(route('transaksi.index'));
    expect(Transaksi::sole()->total)->toBe('9500.00')->and(TransaksiDetail::count())->toBe(2)
        ->and($a->fresh()->stok)->toBe(8)->and($b->fresh()->stok)->toBe(7)->and($other->fresh()->stok)->toBe(10);
    $this->assertDatabaseHas('transaksi_details', ['produk_id' => $a->id, 'jumlah' => 2, 'subtotal' => 2000]);
    $this->assertDatabaseHas('transaksi_details', ['produk_id' => $b->id, 'jumlah' => 3, 'subtotal' => 7500]);
});

test('browser prices totals ids and stock values are ignored', function () {
    $product = saleProduct(['harga' => 12500]);
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
        'produk_id' => [$product->id], 'jumlah' => [2], 'harga' => [1], 'harga_satuan' => [0],
        'subtotal' => [-1], 'total' => 1, 'stok' => 999, 'id' => 999999,
        'kode_transaksi' => 'FORGED', 'transaksi_id' => 999999,
    ])->assertSessionHasNoErrors()->assertRedirect(route('transaksi.index'));
    expect(Transaksi::sole()->total)->toBe('25000.00')->and(Transaksi::sole()->kode_transaksi)->not->toBe('FORGED')
        ->and(TransaksiDetail::sole()->harga_satuan)->toBe('12500.00')->and($product->fresh()->stok)->toBe(8);
});

test('duplicate product entries are combined into exactly one detail', function () {
    $product = saleProduct();
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id, (string) $product->id], 'jumlah' => [2, 3]])
        ->assertSessionHasNoErrors()->assertRedirect(route('transaksi.index'));
    expect(TransaksiDetail::count())->toBe(1)->and(TransaksiDetail::sole()->jumlah)->toBe(5)
        ->and(Transaksi::sole()->total)->toBe('62500.00')->and($product->fresh()->stok)->toBe(5);
});

test('combined duplicate quantities must fit stock and the signed integer column', function ($quantities) {
    $product = saleProduct(['harga' => 0, 'stok' => 2147483647]);
    if ($quantities === [6, 5]) {
        $product->update(['stok' => 10]);
    }
    $stock = $product->stok;
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id, $product->id], 'jumlah' => $quantities])
        ->assertUnprocessable()->assertJsonValidationErrors('jumlah');
    assertNoSale();
    expect($product->fresh()->stok)->toBe($stock);
})->with([[ [6, 5] ], [ [2147483647, 1] ]]);

test('insufficient or exhausted stock rejects the entire cart', function ($stock) {
    $first = saleProduct();
    $second = saleProduct(['stok' => $stock]);
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$first->id, $second->id], 'jumlah' => [1, 3]])
        ->assertUnprocessable()->assertJsonValidationErrors('jumlah');
    assertNoSale();
    expect($first->fresh()->stok)->toBe(10)->and($second->fresh()->stok)->toBe($stock);
})->with([0, 2]);

test('missing products reject the whole cart with a validation error', function () {
    $product = saleProduct();
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id, 999999], 'jumlah' => [1, 1]])
        ->assertUnprocessable()->assertJsonValidationErrors('produk_id');
    assertNoSale();
    expect($product->fresh()->stok)->toBe(10);
});

test('invalid quantities cannot create a sale', function ($quantity) {
    $product = saleProduct();
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [$quantity]])
        ->assertUnprocessable()->assertJsonValidationErrors('jumlah.0');
    assertNoSale();
    expect($product->fresh()->stok)->toBe(10);
})->with([0, -1, '1.5', 1.5, 'abc', null, true, ['nested'], 2147483648]);

test('malformed or misaligned carts fail validation without partial changes', function ($cart, $field) {
    saleProduct();
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString()] + $cart)->assertUnprocessable()->assertJsonValidationErrors($field);
    assertNoSale();
    expect(Produk::sole()->stok)->toBe(10);
})->with([
    'empty' => [[], 'produk_id'],
    'scalar products' => [['produk_id' => 1, 'jumlah' => [1]], 'produk_id'],
    'scalar quantity' => [['produk_id' => [1], 'jumlah' => 1], 'jumlah'],
    'missing quantity' => [['produk_id' => [1, 1], 'jumlah' => [1]], 'jumlah'],
    'extra quantity' => [['produk_id' => [1], 'jumlah' => [1, 1]], 'jumlah'],
    'sparse quantity keys' => [['produk_id' => [1], 'jumlah' => [2 => 1]], 'jumlah'],
    'associative product keys' => [['produk_id' => ['a' => 1], 'jumlah' => [1]], 'produk_id'],
    'invalid product' => [['produk_id' => ['invalid'], 'jumlah' => [1]], 'produk_id.0'],
    'zero product' => [['produk_id' => [0], 'jumlah' => [1]], 'produk_id.0'],
    'negative product' => [['produk_id' => [-1], 'jumlah' => [1]], 'produk_id.0'],
    'nested product' => [['produk_id' => [[1]], 'jumlah' => [1]], 'produk_id.0'],
    'boolean product' => [['produk_id' => [true], 'jumlah' => [1]], 'produk_id.0'],
    'oversize cart' => [['produk_id' => array_fill(0, 101, 1), 'jumlah' => array_fill(0, 101, 1)], 'produk_id'],
]);

test('zero price and exactly exhausted stock are supported', function () {
    $product = saleProduct(['harga' => 0, 'stok' => 3]);
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [3]])->assertSessionHasNoErrors();
    expect(Transaksi::sole()->total)->toBe('0.00')->and(TransaksiDetail::sole()->subtotal)->toBe('0.00')
        ->and($product->fresh()->stok)->toBe(0);
});

test('decimal column whole rupiah limit is accepted exactly', function () {
    $product = saleProduct(['harga' => 1, 'stok' => 2147483647]);
    $second = saleProduct(['harga' => 2147483647, 'stok' => 4]);
    // 4 * 2147483647 + 1410065411 = 9999999999.
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id, $second->id], 'jumlah' => [1410065411, 4]])
        ->assertSessionHasNoErrors();
    expect(Transaksi::sole()->total)->toBe('9999999999.00');
});

test('subtotal and combined total overflow are rejected before writing', function ($combined) {
    $a = saleProduct(['harga' => 2147483647, 'stok' => 2147483647]);
    $b = saleProduct(['harga' => 2147483647, 'stok' => 2147483647]);
    $cart = $combined ? ['produk_id' => [$a->id, $b->id], 'jumlah' => [3, 2]] : ['produk_id' => [$a->id], 'jumlah' => [2147483647]];
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString()] + $cart)->assertUnprocessable()->assertJsonValidationErrors('jumlah');
    assertNoSale();
    expect($a->fresh()->stok)->toBe(2147483647)->and($b->fresh()->stok)->toBe(2147483647);
})->with([true, false]);

test('invalid stored price is rejected', function () {
    $product = saleProduct(['harga' => -1]);
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [1]])
        ->assertUnprocessable()->assertJsonValidationErrors('produk_id');
    assertNoSale();
    expect($product->fresh()->stok)->toBe(10);
});

test('database failure at any write stage rolls back header details and all stock', function ($stage) {
    $a = saleProduct();
    $b = saleProduct();
    $sql = match ($stage) {
        'header' => "CREATE TRIGGER fail_sale BEFORE INSERT ON transaksis BEGIN SELECT RAISE(ABORT, 'test write failure'); END",
        'second detail' => "CREATE TRIGGER fail_sale BEFORE INSERT ON transaksi_details WHEN NEW.produk_id = {$b->id} BEGIN SELECT RAISE(ABORT, 'test write failure'); END",
        'second stock update' => "CREATE TRIGGER fail_sale BEFORE UPDATE OF stok ON produks WHEN OLD.id = {$b->id} BEGIN SELECT RAISE(ABORT, 'test write failure'); END",
        'final total update' => "CREATE TRIGGER fail_sale BEFORE UPDATE OF total ON transaksis BEGIN SELECT RAISE(ABORT, 'test write failure'); END",
    };
    // This trigger exists only in the already-guarded SQLite :memory: connection.
    DB::unprepared($sql);
    try {
        $this->from(route('transaksi.create'))->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$a->id, $b->id], 'jumlah' => [2, 3]])
            ->assertRedirect(route('transaksi.create'))->assertSessionHasErrors('transaksi');
        assertNoSale();
        expect($a->fresh()->stok)->toBe(10)->and($b->fresh()->stok)->toBe(10);
    } finally {
        DB::unprepared('DROP TRIGGER fail_sale');
    }
})->with(['header', 'second detail', 'second stock update', 'final total update']);

test('many sales within the same timestamp have unique transaction numbers', function () {
    $this->freezeTime();
    $product = saleProduct(['stok' => 20]);
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [1]])->assertSessionHasNoErrors();
    }
    expect(Transaksi::count())->toBe(10)->and(Transaksi::pluck('kode_transaksi')->unique()->count())->toBe(10)
        ->and(TransaksiDetail::count())->toBe(10)->and($product->fresh()->stok)->toBe(10);
});

test('a rejected guarded stock update rolls back the sale instead of persisting partial data', function () {
    $product = saleProduct();
    DB::unprepared('CREATE TRIGGER reject_stock BEFORE UPDATE OF stok ON produks BEGIN SELECT RAISE(IGNORE); END');
    try {
        $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [2]])
            ->assertUnprocessable()->assertJsonValidationErrors('jumlah');
        assertNoSale();
        expect($product->fresh()->stok)->toBe(10);
    } finally {
        DB::unprepared('DROP TRIGGER reject_stock');
    }
});

test('a transaction code collision is retried without a partial sale', function () {
    $uuid = '00000000-0000-4000-8000-000000000001';
    Transaksi::create(['kode_transaksi' => 'TRX-'.$uuid, 'total' => 0]);
    Str::createUuidsUsingSequence([$uuid, '00000000-0000-4000-8000-000000000002']);
    $product = saleProduct();
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [2]])->assertSessionHasNoErrors();
    expect(Transaksi::count())->toBe(2)->and(TransaksiDetail::count())->toBe(1)->and($product->fresh()->stok)->toBe(8);
});

test('exhausted transaction code retries do not change stock or leave details', function () {
    $uuid = '00000000-0000-4000-8000-000000000001';
    Transaksi::create(['kode_transaksi' => 'TRX-'.$uuid, 'total' => 0]);
    Str::createUuidsUsing(fn () => $uuid);
    $product = saleProduct();
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [2]])
        ->assertUnprocessable()->assertJsonValidationErrors('transaksi');
    expect(Transaksi::count())->toBe(1)->and(TransaksiDetail::count())->toBe(0)->and($product->fresh()->stok)->toBe(10);
});

test('a later request with stale stock cannot oversell the last units', function () {
    $product = saleProduct(['stok' => 3]);
    $cart = ['produk_id' => [$product->id], 'jumlah' => [2]];
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString()] + $cart)->assertRedirect(route('transaksi.index'));
    $this->postJson(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString()] + $cart)->assertUnprocessable()->assertJsonValidationErrors('jumlah');
    expect(Transaksi::count())->toBe(1)->and(TransaksiDetail::count())->toBe(1)->and($product->fresh()->stok)->toBe(1);
});

test('products are read within the atomic operation in primary key order', function () {
    $a = saleProduct();
    $b = saleProduct();
    $reads = [];
    DB::listen(function ($query) use (&$reads) {
        if (str_starts_with($query->sql, 'select * from "produks" where "produks"."id" =')) {
            $reads[] = ['id' => $query->bindings[0], 'level' => DB::transactionLevel()];
        }
    });
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$b->id, $a->id], 'jumlah' => [1, 1]])->assertSessionHasNoErrors();
    expect(array_column($reads, 'id'))->toBe([$a->id, $b->id]);
    foreach ($reads as $read) {
        // RefreshDatabase holds an outer transaction; the sale adds its own scope.
        expect($read['level'])->toBeGreaterThanOrEqual(2);
    }
});

test('mysql grammar compiles an exclusive stock row lock without connecting to MariaDB', function () {
    $connection = new MySqlConnection(null, 'sql_compilation_only');
    $sql = $connection->table('produks')->where('id', 1)->lockForUpdate()->toSql();
    expect($sql)->toEndWith('for update');
});

test('transaction pages render branding empty states pagination and missing detail', function () {
    $this->get(route('transaksi.index'))->assertOk()->assertSee('Belum ada data.')->assertSee('Run')->assertSee('way');
    $this->get(route('transaksi.create'))->assertOk()->assertSee('Tidak ada produk dengan stok tersedia.');
    saleProduct(['nama' => 'HABIS', 'stok' => 0]);
    $available = saleProduct(['nama' => 'TERSEDIA']);
    $this->get(route('transaksi.create'))->assertOk()->assertSee('TERSEDIA')->assertDontSee('HABIS');
    for ($i = 0; $i < 11; $i++) {
        Transaksi::create(['kode_transaksi' => 'PAGE-'.$i, 'total' => 0]);
    }
    $this->get(route('transaksi.index'))->assertOk()->assertSee('PAGE-10')->assertDontSee('PAGE-0<', false);
    $this->get(route('transaksi.index', ['page' => 2]))->assertOk()->assertSee('PAGE-0');
    $this->get(route('transaksi.show', 999999))->assertNotFound();
    expect($available->fresh()->stok)->toBe(10);
});

test('invalid submission restores selected products and quantity in the form', function () {
    $product = saleProduct();
    $this->from(route('transaksi.create'))->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [11]])
        ->assertSessionHasErrors('jumlah')->assertRedirect(route('transaksi.create'));
    $this->get(route('transaksi.create'))->assertOk()->assertSee('Stok produk')->assertSee('value="11"', false)->assertSee('checked', false);
});

test('guest and non admin users cannot list create view or store transactions', function () {
    $product = saleProduct();
    $sale = Transaksi::create(['kode_transaksi' => 'ACCESS-TEST', 'total' => 0]);
    $urls = [route('transaksi.index'), route('transaksi.create'), route('transaksi.show', $sale)];
    $this->app['auth']->forgetGuards();
    foreach ($urls as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [1]])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create());
    foreach ($urls as $url) {
        $this->get($url)->assertForbidden();
    }
    $this->post(route('transaksi.store'), ['idempotency_key' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [1]])->assertForbidden();
    expect(Transaksi::count())->toBe(1)->and(TransaksiDetail::count())->toBe(0)->and($product->fresh()->stok)->toBe(10);
});
