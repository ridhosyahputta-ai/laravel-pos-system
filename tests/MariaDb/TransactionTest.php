<?php

namespace Tests\MariaDb;

use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;

final class TransactionTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($this->admin);
    }

    private function product(int $stock = 10, int $price = 12500): Produk
    {
        return Produk::create(['nama' => 'MariaDB Runway', 'kode' => bin2hex(random_bytes(8)), 'harga' => $price, 'stok' => $stock]);
    }

    private function cart(Produk $product, int $quantity = 2): array
    {
        return ['idempotency_key' => Uuid::uuid4()->toString(), 'produk_id' => [$product->id], 'jumlah' => [$quantity]];
    }

    public function testMigrationsAndNativeSchema(): void
    {
        $this->assertSame('db_runway_testing', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertSame(19, DB::table('migrations')->count());
        foreach (['produks', 'transaksis', 'transaksi_details'] as $table) {
            $row = DB::selectOne('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table]);
            $this->assertSame('InnoDB', $row->engine);
        }
        $column = DB::selectOne("SELECT DATA_TYPE AS type, IS_NULLABLE AS nullable FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='kategoris' AND COLUMN_NAME='keterangan'");
        $this->assertSame('text', $column->type);
        $this->assertSame('YES', $column->nullable);
        $this->assertTrue(Schema::hasColumn('transaksis', 'request_hash'));
        $index = DB::selectOne("SELECT NON_UNIQUE AS non_unique FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='transaksis' AND INDEX_NAME='transaksis_idempotency_key_unique'");
        $this->assertSame(0, (int) $index->non_unique);
    }

    public function testNormalMultipleProductSaleAndPriceManipulation(): void
    {
        $a = $this->product();
        $b = $this->product(5, 2000);
        $cart = $this->cart($a, 3);
        $cart['produk_id'][] = $b->id;
        $cart['jumlah'][] = 2;
        $this->post(route('transaksi.store'), $cart + ['harga' => [1, 1], 'total' => 1])
            ->assertRedirect(route('transaksi.index'))->assertSessionHasNoErrors();
        $this->assertSame('41500.00', Transaksi::sole()->total);
        $this->assertSame(2, TransaksiDetail::count());
        $this->assertSame(7, $a->fresh()->stok);
        $this->assertSame(3, $b->fresh()->stok);
        $this->get(route('transaksi.show', Transaksi::sole()))->assertOk();
    }

    public function testCategoryCorrectionPreservesLegacyDataOnNativeMariaDb(): void
    {
        DB::statement('ALTER TABLE kategoris MODIFY keterangan VARCHAR(50) NOT NULL');
        $id = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Legacy', 'keterangan' => 'Preserved legacy description']);
        $migration = require database_path('migrations/2026_10_18_000001_normalize_keterangan_on_kategoris_table.php');
        $migration->up();
        $this->assertSame('Preserved legacy description', DB::table('kategoris')->where('id', $id)->value('keterangan'));
        DB::table('kategoris')->where('id', $id)->update(['keterangan' => str_repeat('x', 2000)]);
        $this->assertSame(2000, strlen(DB::table('kategoris')->where('id', $id)->value('keterangan')));
        DB::table('kategoris')->where('id', $id)->update(['keterangan' => null]);
        $this->assertNull(DB::table('kategoris')->where('id', $id)->value('keterangan'));
    }

    public function testIdempotencyMigrationPreservesLegacySalesOnNativeMariaDb(): void
    {
        $migration = require database_path('migrations/2026_10_18_000002_add_idempotency_to_transaksis_table.php');
        $migration->down();
        $id = DB::table('transaksis')->insertGetId(['kode_transaksi' => 'LEGACY-NATIVE', 'total' => 12500]);
        $migration->up();
        $this->assertSame('12500.00', Transaksi::findOrFail($id)->total);
        $this->assertNull(Transaksi::findOrFail($id)->idempotency_key);
        $this->post(route('transaksi.store'), $this->cart($this->product()))->assertSessionHasNoErrors();
        $this->assertSame(2, Transaksi::count());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot remove idempotency history');
        $migration->down();
    }

    public function testNativePdfAndAdminAccessRemainAvailable(): void
    {
        $this->product();
        $this->get(route('produk.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf')->assertDownload('data-produk.pdf');
        $this->actingAs(User::factory()->create())->get(route('transaksi.index'))->assertForbidden();
        $this->post(route('transaksi.store'), $this->cart(Produk::sole()))->assertForbidden();
        $this->assertSame(0, Transaksi::count());
    }

    public function testInsufficientStockRollsBackTheTokenClaim(): void
    {
        $a = $this->product();
        $b = $this->product(1);
        $cart = $this->cart($a);
        $cart['produk_id'][] = $b->id;
        $cart['jumlah'][] = 2;
        $this->postJson(route('transaksi.store'), $cart)->assertUnprocessable()->assertJsonValidationErrors('jumlah');
        $this->assertSame(0, Transaksi::count());
        $this->assertSame(0, TransaksiDetail::count());
        $this->assertSame(10, $a->fresh()->stok);
        $this->assertSame(1, $b->fresh()->stok);
    }

    public function testWibPresentationDoesNotChangeStoredUtcDates(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-18 18:30:00 UTC');
        try {
            $this->post(route('transaksi.store'), $this->cart($this->product()))->assertSessionHasNoErrors();
            $sale = Transaksi::sole();
            $before = DB::table('transaksis')->where('id', $sale->id)->first();
            $this->assertSame('2026-10-18 18:30:00', $before->created_at);
            $this->get(route('transaksi.index'))->assertOk()->assertSee('19/10/2026 01:30 WIB');
            $this->get(route('transaksi.show', $sale))->assertOk()->assertSee('19/10/2026 01:30 WIB');
            $after = DB::table('transaksis')->where('id', $sale->id)->first();
            $this->assertSame($before->created_at, $after->created_at);
            $this->assertSame($before->updated_at, $after->updated_at);
            $this->assertSame('UTC', config('app.timezone'));
            $this->assertSame(1, Transaksi::count());
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }

    public function testNativeTriggerFailureRollsBackAllWritesAndAllowsRetry(): void
    {
        $a = $this->product();
        $b = $this->product();
        $cart = $this->cart($a);
        $cart['produk_id'][] = $b->id;
        $cart['jumlah'][] = 3;
        DB::unprepared("CREATE TRIGGER runway_fail_detail BEFORE INSERT ON transaksi_details FOR EACH ROW BEGIN IF NEW.produk_id = {$b->id} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'isolated rollback test'; END IF; END");
        try {
            $this->postJson(route('transaksi.store'), $cart)->assertUnprocessable()->assertJsonValidationErrors('transaksi');
            $this->assertSame(0, Transaksi::count());
            $this->assertSame(0, TransaksiDetail::count());
            $this->assertSame(10, $a->fresh()->stok);
            $this->assertSame(10, $b->fresh()->stok);
        } finally {
            DB::unprepared('DROP TRIGGER runway_fail_detail');
        }
        $this->post(route('transaksi.store'), $cart)->assertSessionHasNoErrors();
        $this->assertSame(1, Transaksi::count());
        $this->assertSame(2, TransaksiDetail::count());
    }

    public function testSameTokenReplaysAndDifferentTokenCreatesAnotherSale(): void
    {
        $product = $this->product();
        $cart = $this->cart($product);
        $this->post(route('transaksi.store'), $cart)->assertSessionHasNoErrors();
        $this->post(route('transaksi.store'), $cart)->assertSessionHasNoErrors();
        $this->assertSame(1, Transaksi::count());
        $this->assertSame(8, $product->fresh()->stok);
        $cart['idempotency_key'] = Uuid::uuid4()->toString();
        $this->post(route('transaksi.store'), $cart)->assertSessionHasNoErrors();
        $this->assertSame(2, Transaksi::count());
        $this->assertSame(6, $product->fresh()->stok);
    }

    public static function masterModules(): array
    {
        return [
            ['produk', 'produks', 'nama', ['nama' => 'Native Product', 'kode' => 'NATIVE', 'harga' => 1000, 'stok' => 10]],
            ['kategori', 'kategoris', 'nama_kategori', ['nama_kategori' => 'Native Category', 'keterangan' => str_repeat('x', 2000)]],
            ['supplier', 'suppliers', 'nama_supplier', ['nama_supplier' => 'Native Supplier']],
            ['pelanggan', 'pelanggans', 'nama', ['nama' => 'Native Customer']],
            ['cabang', 'cabangs', 'nama_cabang', ['nama_cabang' => 'Native Branch']],
            ['petugas', 'petugas', 'nama', ['nama' => 'Native Staff', 'username' => 'native_staff', 'role' => 'Kasir']],
        ];
    }

    #[DataProvider('masterModules')]
    public function testNativeMasterCrud(string $module, string $table, string $name, array $data): void
    {
        $this->post(route($module.'.store'), $data)->assertRedirect(route($module.'.index'))->assertSessionHasNoErrors();
        $id = DB::table($table)->sole()->id;
        $this->get(route($module.'.edit', $id))->assertOk();
        $data[$name] = 'Native Updated';
        $this->put(route($module.'.update', $id), $data)->assertSessionHasNoErrors();
        $this->assertSame('Native Updated', DB::table($table)->where('id', $id)->value($name));
        $this->delete(route($module.'.destroy', $id))->assertRedirect(route($module.'.index'));
        $this->assertSame(0, DB::table($table)->count());
    }

    private function directory(): string
    {
        $path = storage_path('framework/testing/mariadb-'.bin2hex(random_bytes(8)));
        mkdir($path, 0700, true);

        return $path;
    }

    private function waitFor(string $directory, string $file): void
    {
        $deadline = microtime(true) + 20;
        while (! is_file($directory.'/'.$file)) {
            if (microtime(true) >= $deadline) {
                $this->fail('Child process barrier timed out: '.$file);
            }
            usleep(5000);
        }
    }

    private function startWorker(string $directory, string $label, array $input): Process
    {
        $process = new Process([PHP_BINARY, __DIR__.'/worker.php', json_encode($input + ['directory' => $directory, 'label' => $label], JSON_THROW_ON_ERROR)], base_path());
        $process->setTimeout(45);
        $process->start();

        return $process;
    }

    private function workerResult(Process $process): array
    {
        $process->wait();
        $this->assertSame(0, $process->getExitCode(), 'Isolated child failed: '.$process->getOutput());
        $result = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
        $this->assertNotSame('error', $result['status']);

        return $result;
    }

    private function stopWorkers(array $workers): void
    {
        foreach ($workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop(1); // Only these newly-created test processes.
            }
        }
    }

    public static function races(): array
    {
        return [['same token', false], ['same token', true], ['different token', false]];
    }

    #[DataProvider('races')]
    public function testRealOverlappingRequests(string $mode, bool $differentCart): void
    {
        $product = $this->product($mode === 'same token' ? 10 : 3);
        $key = Uuid::uuid4()->toString();
        $directory = $this->directory();
        $input = ['products' => [$product->id], 'quantities' => [2], 'token' => $key, 'user_id' => $this->admin->id];
        $a = $this->startWorker($directory, 'a', $input + ['hold' => $mode === 'same token' ? 'claim' : 'stock']);
        $bInput = array_replace($input, ['quantities' => [$differentCart ? 3 : 2], 'token' => $mode === 'same token' ? $key : Uuid::uuid4()->toString(), 'lock_target' => $product->id]);
        $b = $this->startWorker($directory, 'b', $bInput);
        try {
            $this->waitFor($directory, 'ready-a');
            $this->waitFor($directory, 'ready-b');
            touch($directory.'/start-a');
            $this->waitFor($directory, 'held-a');
            touch($directory.'/start-b');
            $this->waitFor($directory, $mode === 'same token' ? 'attempting-claim-b' : 'attempting-lock-b');
            usleep(200000);
            $this->assertTrue($b->isRunning(), 'Second independent connection must wait for the first transaction.');
            touch($directory.'/release-a');
            $first = $this->workerResult($a);
            $second = $this->workerResult($b);
            $this->assertNotSame($first['connection_id'], $second['connection_id']);
            $this->assertSame('ok', $first['status']);
            if ($mode === 'same token' && ! $differentCart) {
                $this->assertSame('ok', $second['status']);
                $this->assertSame($first['id'], $second['id']);
                $this->assertTrue($second['replayed']);
            } else {
                $this->assertSame('validation', $second['status']);
                $this->assertContains($differentCart ? 'idempotency_key' : 'jumlah', $second['fields']);
            }
            $this->assertSame(1, Transaksi::count());
            $this->assertSame(1, TransaksiDetail::count());
            $this->assertSame($mode === 'same token' ? 8 : 1, $product->fresh()->stok);
            file_put_contents($directory.'/result.json', json_encode([$first, $second], JSON_PRETTY_PRINT));
        } finally {
            touch($directory.'/release-a');
            $this->stopWorkers([$a, $b]);
        }
    }

    public function testRealConcurrentSalesHaveUniqueNumbersAtTheSameTimestamp(): void
    {
        $a = $this->product();
        $b = $this->product();
        $directory = $this->directory();
        $workers = [];
        try {
            foreach (['a' => $a, 'b' => $b] as $label => $product) {
                $workers[] = $this->startWorker($directory, $label, ['products' => [$product->id], 'quantities' => [2], 'token' => Uuid::uuid4()->toString(), 'user_id' => $this->admin->id]);
            }
            $this->waitFor($directory, 'ready-a');
            $this->waitFor($directory, 'ready-b');
            touch($directory.'/start-a');
            touch($directory.'/start-b');
            $first = $this->workerResult($workers[0]);
            $second = $this->workerResult($workers[1]);
            $this->assertNotSame($first['connection_id'], $second['connection_id']);
            $this->assertSame('ok', $first['status']);
            $this->assertSame('ok', $second['status']);
            $this->assertSame(2, Transaksi::count());
            $this->assertSame(2, Transaksi::pluck('kode_transaksi')->unique()->count());
            $this->assertSame(1, Transaksi::pluck('created_at')->unique()->count());
            $this->assertSame(8, $a->fresh()->stok);
            $this->assertSame(8, $b->fresh()->stok);
        } finally {
            $this->stopWorkers($workers);
        }
    }

    public function testRealInnoDbDeadlockRetriesTheWholeSale(): void
    {
        $a = $this->product();
        $b = $this->product();
        $weight = [];
        for ($i = 0; $i < 25; $i++) {
            $weight[] = $this->product()->id;
        }
        $directory = $this->directory();
        $blocker = $this->startWorker($directory, 'blocker', ['mode' => 'blocker', 'weight_ids' => $weight, 'first_id' => $b->id, 'second_id' => $a->id]);
        $sale = $this->startWorker($directory, 'sale', ['products' => [$a->id, $b->id], 'quantities' => [2, 3], 'token' => Uuid::uuid4()->toString(), 'user_id' => $this->admin->id, 'lock_target' => $b->id]);
        try {
            $this->waitFor($directory, 'ready-blocker');
            $this->waitFor($directory, 'ready-sale');
            touch($directory.'/start-blocker');
            $this->waitFor($directory, 'held-blocker');
            touch($directory.'/start-sale');
            $this->waitFor($directory, 'attempting-lock-sale');
            usleep(100000);
            $this->assertTrue($sale->isRunning());
            touch($directory.'/release-blocker');
            $blockerResult = $this->workerResult($blocker);
            $saleResult = $this->workerResult($sale);
            $this->assertNotSame($blockerResult['connection_id'], $saleResult['connection_id']);
            $this->assertSame('ok', $blockerResult['status']);
            $this->assertSame('ok', $saleResult['status']);
            $this->assertGreaterThanOrEqual(2, $saleResult['attempts'], 'The sale must retry after the native deadlock.');
            $this->assertSame(1, Transaksi::count());
            $this->assertSame(2, TransaksiDetail::count());
            $this->assertSame('62500.00', Transaksi::sole()->total);
            $this->assertSame(8, $a->fresh()->stok);
            $this->assertSame(7, $b->fresh()->stok);
            file_put_contents($directory.'/result.json', json_encode(['blocker' => $blockerResult, 'sale' => $saleResult], JSON_PRETTY_PRINT));
        } finally {
            touch($directory.'/release-blocker');
            $this->stopWorkers([$blocker, $sale]);
        }
    }
}
