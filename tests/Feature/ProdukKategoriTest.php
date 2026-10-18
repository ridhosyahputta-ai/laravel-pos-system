<?php

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    $this->actingAs($admin);
});

function productPayload(array $attributes = []): array
{
    return array_replace([
        'nama' => 'Sepatu Runway',
        'kode' => 'RUN-001',
        'harga' => 250000,
        'stok' => 12,
        'kategori_id' => null,
        'supplier_id' => null,
        'keterangan' => null,
    ], $attributes);
}

test('product and category pages render with the existing Runway branding', function () {
    $kategori = Kategori::create(['nama_kategori' => 'Sneakers', 'keterangan' => null]);
    $produk = Produk::create(productPayload(['kategori_id' => $kategori->id]));

    foreach ([route('produk.index'), route('produk.create'), route('produk.edit', $produk),
        route('kategori.index'), route('kategori.create'), route('kategori.edit', $kategori)] as $url) {
        $this->get($url)->assertOk()->assertSee('Run')->assertSee('way');
    }
});

test('unused detail routes are not registered', function () {
    expect(Route::has('produk.show'))->toBeFalse()
        ->and(Route::has('kategori.show'))->toBeFalse();
});

test('admin can create update and delete an unused product', function () {
    $kategori = Kategori::create(['nama_kategori' => 'Sneakers']);
    $supplier = Supplier::create(['nama_supplier' => 'Supplier Test']);
    $data = productPayload(['kategori_id' => $kategori->id, 'supplier_id' => $supplier->id]);

    $this->post(route('produk.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('produk.index'));
    $produk = Produk::where('kode', $data['kode'])->sole();
    expect($produk->kategori->is($kategori))->toBeTrue()
        ->and($produk->supplier->is($supplier))->toBeTrue();

    $this->put(route('produk.update', $produk), productPayload([
        'nama' => 'Runway Baru', 'harga' => 0, 'stok' => 0, 'keterangan' => 'Diperbarui',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('produk.index'));
    $this->assertDatabaseHas('produks', ['id' => $produk->id, 'nama' => 'Runway Baru', 'harga' => 0, 'stok' => 0, 'kategori_id' => null, 'supplier_id' => null]);

    $this->delete(route('produk.destroy', $produk))->assertRedirect(route('produk.index'))->assertSessionHas('success');
    $this->assertDatabaseMissing('produks', ['id' => $produk->id]);
});

test('product validation rejects invalid input on create and update', function ($field, $value) {
    $produk = Produk::create(productPayload(['kode' => 'EXISTING']));
    $count = Produk::count();
    $this->post(route('produk.store'), productPayload([$field => $value]))->assertSessionHasErrors($field);
    $this->put(route('produk.update', $produk), productPayload([$field => $value]))->assertSessionHasErrors($field);
    expect(Produk::count())->toBe($count)->and($produk->fresh()->kode)->toBe('EXISTING');
})->with([
    'empty name' => ['nama', ''],
    'empty code' => ['kode', ''],
    'negative price' => ['harga', -1],
    'fractional price' => ['harga', '1.5'],
    'price overflow' => ['harga', 2147483648],
    'negative stock' => ['stok', -1],
    'fractional stock' => ['stok', '1.5'],
    'stock overflow' => ['stok', 2147483648],
    'missing category' => ['kategori_id', 999999],
    'invalid category type' => ['kategori_id', 'invalid'],
    'missing supplier' => ['supplier_id', 999999],
    'long description' => ['keterangan', str_repeat('x', 2001)],
]);

test('duplicate product codes are rejected without changing either product', function () {
    $first = Produk::create(productPayload());
    $second = Produk::create(productPayload(['kode' => 'RUN-002']));
    $this->post(route('produk.store'), productPayload())->assertSessionHasErrors('kode');
    $this->put(route('produk.update', $second), productPayload())->assertSessionHasErrors('kode');
    expect($first->fresh()->kode)->toBe('RUN-001')->and($second->fresh()->kode)->toBe('RUN-002');
});

test('maximum signed integer price and stock are accepted', function () {
    $this->post(route('produk.store'), productPayload(['harga' => 2147483647, 'stok' => 2147483647]))
        ->assertSessionHasNoErrors()->assertRedirect(route('produk.index'));
    $this->assertDatabaseHas('produks', ['harga' => 2147483647, 'stok' => 2147483647]);
});

test('a product referenced by a transaction cannot be deleted and the message is visible', function () {
    $produk = Produk::create(productPayload());
    $transaksi = Transaksi::create(['kode_transaksi' => 'TEST-USED', 'total' => $produk->harga]);
    $detail = $transaksi->detail()->create(['produk_id' => $produk->id, 'jumlah' => 1, 'harga_satuan' => $produk->harga, 'subtotal' => $produk->harga]);

    $this->delete(route('produk.destroy', $produk))->assertRedirect(route('produk.index'))->assertSessionHas('error');
    $this->assertDatabaseHas('produks', ['id' => $produk->id, 'stok' => 12]);
    $this->assertDatabaseHas('transaksi_details', ['id' => $detail->id]);
    $this->get(route('produk.index'))->assertOk()->assertSee('Produk tidak dapat dihapus');
});

test('admin can create and update categories with optional or long descriptions', function ($description) {
    $this->post(route('kategori.store'), ['nama_kategori' => 'Sneakers', 'keterangan' => $description])
        ->assertSessionHasNoErrors()->assertRedirect(route('kategori.index'));
    $kategori = Kategori::sole();
    expect($kategori->keterangan)->toBe($description === '' ? null : $description);

    $this->put(route('kategori.update', $kategori), ['nama_kategori' => 'Updated', 'keterangan' => $description])
        ->assertSessionHasNoErrors()->assertRedirect(route('kategori.index'));
    expect($kategori->fresh()->nama_kategori)->toBe('Updated');
})->with([null, '', str_repeat('a', 51), str_repeat('a', 2000)]);

test('category validation rejects missing names and descriptions beyond the limit', function () {
    $kategori = Kategori::create(['nama_kategori' => 'Original']);
    foreach ([['nama_kategori' => ''], ['nama_kategori' => 'New', 'keterangan' => str_repeat('x', 2001)]] as $payload) {
        $field = $payload['nama_kategori'] === '' ? 'nama_kategori' : 'keterangan';
        $this->post(route('kategori.store'), $payload)->assertSessionHasErrors($field);
        $this->put(route('kategori.update', $kategori), $payload)->assertSessionHasErrors($field);
    }
    expect(Kategori::count())->toBe(1)->and($kategori->fresh()->nama_kategori)->toBe('Original');
});

test('deleting a category preserves its products and clears their category reference', function () {
    $kategori = Kategori::create(['nama_kategori' => 'Sneakers']);
    $produk = Produk::create(productPayload(['kategori_id' => $kategori->id]));
    $this->delete(route('kategori.destroy', $kategori))->assertRedirect(route('kategori.index'));
    $this->assertDatabaseMissing('kategoris', ['id' => $kategori->id]);
    $this->assertDatabaseHas('produks', ['id' => $produk->id, 'kategori_id' => null, 'stok' => 12]);
});

test('missing product and category records return 404', function () {
    foreach (['produk', 'kategori'] as $resource) {
        $this->get(route($resource.'.edit', 999999))->assertNotFound();
        $this->put(route($resource.'.update', 999999), [])->assertNotFound();
        $this->delete(route($resource.'.destroy', 999999))->assertNotFound();
    }
});

test('product PDF export remains a downloadable PDF with empty and populated data', function () {
    foreach ([false, true] as $populated) {
        if ($populated) {
            Produk::create(productPayload());
        }
        $response = $this->get(route('produk.pdf'));
        $response->assertOk()->assertHeader('content-type', 'application/pdf')->assertDownload('data-produk.pdf');
        expect($response->getContent())->toStartWith('%PDF-');
    }
});

test('guests and users without admin role cannot use product or category CRUD', function () {
    $this->app['auth']->forgetGuards();
    $guestRoutes = ['produk.index', 'produk.create', 'kategori.index', 'kategori.create', 'produk.pdf'];
    foreach ($guestRoutes as $name) {
        $this->get(route($name))->assertRedirect(route('login'));
    }
    $user = User::factory()->create();
    $this->actingAs($user);
    foreach ($guestRoutes as $name) {
        $this->get(route($name))->assertForbidden();
    }
    $this->post(route('produk.store'), productPayload())->assertForbidden();
    $this->post(route('kategori.store'), ['nama_kategori' => 'Denied'])->assertForbidden();
    expect(Produk::count())->toBe(0)->and(Kategori::count())->toBe(0);
});

test('database safety guard rejects a persistent sqlite database before any query', function () {
    $original = config('database.default');
    try {
        config([
            'database.default' => 'unsafe_sqlite',
            'database.connections.unsafe_sqlite' => ['driver' => 'sqlite', 'database' => 'db_rpld', 'prefix' => ''],
        ]);
        expect(fn () => $this->assertTestDatabaseSafety($this->app))->toThrow(RuntimeException::class, 'SQLite :memory:');
    } finally {
        config(['database.default' => $original]);
        DB::purge('unsafe_sqlite');
    }
});

test('test bootstrap rejects the live database name before booting another application', function () {
    $original = $_ENV['DB_DATABASE'];
    try {
        $_ENV['DB_DATABASE'] = 'db_rpld';
        expect(fn () => $this->createApplication())->toThrow(RuntimeException::class, 'Test bootstrap requires SQLite :memory:');
    } finally {
        $_ENV['DB_DATABASE'] = $original;
    }
});
