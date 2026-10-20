<?php

namespace Tests\MariaDb;

use App\Models\Cabang;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;

// Deliberately does not extend the destructive MariaDb TestCase.
// Preserve pre-existing fixtures: every test rolls back its own writes.
final class CashierFoundationTest extends LaravelTestCase
{
    public function createApplication()
    {
        return Environment::boot();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Environment::assertIsolated($this->app);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function account(string $role): User
    {
        $branch = $role === 'kasir' ? Cabang::create(['nama_cabang' => 'Isolated Foundation']) : null;
        $user = User::factory()->create(['cabang_id' => $branch?->id]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    public function test_native_schema_and_foreign_key_restriction(): void
    {
        $this->assertSame('db_runway_testing', DB::selectOne('SELECT DATABASE() db')->db);
        foreach (['is_active', 'cabang_id'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column));
        }
        foreach (['user_id', 'cabang_id'] as $column) {
            $this->assertTrue(Schema::hasColumn('transaksis', $column));
        }
        $fk = DB::select("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ('users','transaksis')");
        $this->assertCount(3, $fk);
        foreach ($fk as $row) {
            $this->assertSame('RESTRICT', $row->DELETE_RULE);
        }
        $c = $this->account('kasir');
        try {
            Cabang::findOrFail($c->cabang_id)->delete();
            $this->fail('Foreign key must reject branch deletion');
        } catch (QueryException $e) {
            $this->assertSame(1451, $e->errorInfo[1]);
        }
    }

    public function test_cashier_ownership_replay_and_global_stock(): void
    {
        $cashier = $this->account('kasir');
        $other = $this->account('kasir');
        $p = Produk::create(['nama' => 'Foundation', 'kode' => Uuid::uuid4()->toString(), 'harga' => 1000, 'stok' => 5]);
        $cart = ['idempotency_key' => Uuid::uuid4()->toString(), 'produk_id' => [$p->id], 'jumlah' => [2], 'user_id' => $other->id, 'cabang_id' => $other->cabang_id];
        $this->actingAs($cashier)->post('/transaksi', $cart)->assertSessionHasNoErrors();
        $sale = Transaksi::where('idempotency_key', $cart['idempotency_key'])->sole();
        $this->assertSame($cashier->id, $sale->user_id);
        $this->assertSame($cashier->cabang_id, $sale->cabang_id);
        $this->post('/transaksi', $cart)->assertSessionHasNoErrors();
        $this->assertSame(3, $p->fresh()->stok);
        $this->assertSame(1, Transaksi::where('idempotency_key', $cart['idempotency_key'])->count());
        $this->actingAs($other)->get(route('transaksi.show', $sale))->assertForbidden();
        $this->get('/transaksi')->assertDontSee($sale->kode_transaksi);
        $this->get('/dashboard')->assertForbidden();
        $this->actingAs($this->account('admin'))->get(route('transaksi.show', $sale))->assertOk();
    }

    public function test_insufficient_stock_rolls_back_attribution_and_details(): void
    {
        $cashier = $this->account('kasir');
        $p = Produk::create(['nama' => 'Foundation', 'kode' => Uuid::uuid4()->toString(), 'harga' => 1000, 'stok' => 1]);
        $token = Uuid::uuid4()->toString();
        $before = DB::table('transaksi_details')->count();
        $this->actingAs($cashier)->post('/transaksi', ['idempotency_key' => $token, 'produk_id' => [$p->id], 'jumlah' => [2]])->assertSessionHasErrors('jumlah');
        $this->assertFalse(Transaksi::where('idempotency_key', $token)->exists());
        $this->assertSame($before, DB::table('transaksi_details')->count());
        $this->assertSame(1, $p->fresh()->stok);
    }

    public function test_inactive_login_and_existing_session(): void
    {
        $c = $this->account('kasir');
        $this->post('/login', ['email' => $c->email, 'password' => 'password'])->assertRedirect(route('transaksi.index'));
        $c->update(['is_active' => false]);
        $this->get('/transaksi')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post('/login', ['email' => $c->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_role_provisioning_preserves_accounts_and_is_idempotent(): void
    {
        $admin = $this->account('admin');
        $before = hash('sha256', serialize($admin->fresh()->getAttributes()));
        $roleBefore = Role::findByName('admin', 'web')->getAttributes();
        $count = User::count();
        $this->artisan('runway:provision-roles')->assertSuccessful();
        $this->artisan('runway:provision-roles')->assertSuccessful();
        $this->assertSame(1, Role::where('name', 'kasir')->where('guard_name', 'web')->count());
        $this->assertSame($count, User::count());
        $this->assertSame($before, hash('sha256', serialize($admin->fresh()->getAttributes())));
        $this->assertSame($roleBefore, Role::findByName('admin', 'web')->getAttributes());
        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
        $this->actingAs($admin)->get('/kasir')->assertOk();
    }
}
