<?php

namespace Tests\Unit;

use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Presentation fixtures live only in memory. No RefreshDatabase or migrations.
final class TransaksiPresentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Auth::guard('web')->setUser(new User(['name' => 'Preview Admin', 'email' => 'preview@example.invalid']));
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        DB::enableQueryLog();
    }

    public function testQuantityConstraintsAndIdempotencyArePresentInTheRenderedForm(): void
    {
        $product = new Produk(['nama' => 'Preview', 'harga' => 12500, 'stok' => 3]);
        $product->id = 7;
        $token = '00000000-0000-4000-8000-000000000007';
        $html = view('transaksi.create', ['produks' => collect([$product]), 'idempotencyKey' => $token])->render();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $quantity = $xpath->query('//input[@name="jumlah[]"]')->item(0);
        $this->assertNotNull($quantity);
        $this->assertTrue($quantity->hasAttribute('required'));
        $this->assertTrue($quantity->hasAttribute('disabled'));
        $this->assertSame('1', $quantity->getAttribute('min'));
        $this->assertSame('3', $quantity->getAttribute('max'));
        $this->assertSame('1', $quantity->getAttribute('step'));
        $this->assertSame($token, $xpath->query('//input[@name="idempotency_key"]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//form[@id="sale-form"]//input[@name="_token"]')->length);
        $this->assertTrue($xpath->query('//button[@id="sale-submit"]')->item(0)->hasAttribute('disabled'));
        $this->assertSame([], DB::getQueryLog());
    }

    public static function utcDates(): array
    {
        return [
            ['2026-10-18 00:00:00', '18/10/2026 07:00 WIB', '2026-10-18T07:00:00+07:00'],
            ['2026-10-18 18:30:00', '19/10/2026 01:30 WIB', '2026-10-19T01:30:00+07:00'],
            ['2026-12-31 20:00:00', '01/01/2027 03:00 WIB', '2027-01-01T03:00:00+07:00'],
        ];
    }

    #[DataProvider('utcDates')]
    public function testWibConversionDoesNotMutateTheSourceDateOrGlobalTimezone(string $utc, string $expected, string $iso): void
    {
        $date = Carbon::parse($utc, 'UTC');
        $html = Blade::render('<x-transaction-date :value="$date" />', ['date' => $date]);
        $this->assertStringContainsString($expected, $html);
        $this->assertStringContainsString('datetime="'.$iso.'"', $html);
        $this->assertSame($utc, $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->timezoneName);
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame([], DB::getQueryLog());
    }

    public function testBothTransactionViewsDisplayWibWithoutMutatingTheModel(): void
    {
        $sale = new Transaksi;
        $sale->setRawAttributes(['id' => 7, 'kode_transaksi' => 'MEMORY-ONLY', 'total' => '25000.00', 'created_at' => '2026-10-18 18:30:00'], true);
        $sale->setRelation('detail', collect());
        $index = view('transaksi.index', ['transaksis' => new LengthAwarePaginator([$sale], 1, 10)])->render();
        $show = view('transaksi.show', ['transaksi' => $sale])->render();
        foreach ([$index, $show] as $html) {
            $this->assertStringContainsString('19/10/2026 01:30 WIB', $html);
            $this->assertStringContainsString('2026-10-19T01:30:00+07:00', $html);
        }
        $this->assertSame('2026-10-18 18:30:00', $sale->getRawOriginal('created_at'));
        $this->assertSame('UTC', $sale->created_at->timezoneName);
        $this->assertSame([], DB::getQueryLog());
    }

    public function testLegacyMissingDatesHaveANeutralPlaceholder(): void
    {
        $html = Blade::render('<x-transaction-date :value="$date" />', ['date' => null]);
        $this->assertStringContainsString('—', $html);
        $this->assertStringNotContainsString('<time', $html);
        $this->assertSame([], DB::getQueryLog());
    }
}
