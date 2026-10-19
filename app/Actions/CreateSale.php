<?php

namespace App\Actions;

use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSale
{
    // Existing DECIMAL(12,2) columns; product prices are whole rupiah (INT).
    private const MAX_TOTAL = 9999999999;

    public function handle(array $productIds, array $quantities, string $idempotencyKey, int $userId): Transaksi
    {
        $cart = [];
        foreach ($productIds as $index => $id) {
            $id = (int) $id;
            $quantity = ($cart[$id] ?? 0) + (int) $quantities[$index];
            if ($quantity > 2147483647) {
                throw ValidationException::withMessages(['jumlah' => 'Jumlah gabungan produk melebihi batas yang diizinkan.']);
            }
            $cart[$id] = $quantity;
        }

        // Every sale acquires primary-key row locks in the same order, including
        // carts submitted in reverse order, reducing InnoDB deadlock risk.
        ksort($cart, SORT_NUMERIC);

        $idempotencyKey = strtolower($idempotencyKey);
        $requestHash = hash('sha256', json_encode(['user_id' => $userId, 'cart' => $cart], JSON_THROW_ON_ERROR));
        $existing = Transaksi::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $this->replay($existing, $requestHash);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($cart, $idempotencyKey, $requestHash, $userId) {
                    // Claim the token BEFORE stock checks/locks. InnoDB's unique
                    // index serializes concurrent submissions, even for disjoint
                    // carts. The claim rolls back together with every sale write.
                    $actor = User::findOrFail($userId);
                    $sale = Transaksi::create([
                        'user_id' => $actor->id,
                        'cabang_id' => $actor->cabang_id,
                        'kode_transaksi' => 'TRX-'.Str::uuid(),
                        'total' => 0,
                        'idempotency_key' => $idempotencyKey,
                        'request_hash' => $requestHash,
                    ]);
                    $lines = [];
                    $total = 0;
                    foreach ($cart as $id => $quantity) {
                        $product = Produk::query()->lockForUpdate()->find($id);
                        if (! $product) {
                            throw ValidationException::withMessages(['produk_id' => 'Produk yang dipilih tidak ditemukan. Muat ulang daftar produk.']);
                        }
                        if ($quantity > $product->stok) {
                            throw ValidationException::withMessages(['jumlah' => "Stok produk {$product->nama} tidak mencukupi. Sisa stok: {$product->stok}."]);
                        }

                        $price = (int) $product->harga;
                        if ($price < 0 || $price > 2147483647) {
                            throw ValidationException::withMessages(['produk_id' => 'Harga produk tidak valid. Periksa master produk.']);
                        }
                        // Check before multiplying/adding: no float rounding or overflow.
                        if ($price > 0 && $quantity > intdiv(self::MAX_TOTAL - $total, $price)) {
                            throw ValidationException::withMessages(['jumlah' => 'Total transaksi melebihi batas Rp 9.999.999.999. Kurangi jumlah pembelian.']);
                        }
                        $subtotal = $price * $quantity;
                        $total += $subtotal;
                        $lines[] = compact('product', 'quantity', 'price', 'subtotal');
                    }

                    foreach ($lines as $line) {
                        $sale->detail()->create([
                            'produk_id' => $line['product']->id,
                            'jumlah' => $line['quantity'],
                            'harga_satuan' => $line['price'],
                            'subtotal' => $line['subtotal'],
                        ]);
                        $updated = Produk::whereKey($line['product']->id)
                            ->where('stok', '>=', $line['quantity'])
                            ->decrement('stok', $line['quantity']);
                        if ($updated !== 1) {
                            throw ValidationException::withMessages(['jumlah' => 'Stok berubah. Periksa keranjang dan coba kembali.']);
                        }
                    }

                    $sale->update(['total' => $total]);

                    return $sale;
                }, 3); // Retry the whole atomic operation on a database deadlock.
            } catch (UniqueConstraintViolationException $exception) {
                if (str_contains($exception->getMessage(), 'transaksis_idempotency_key_unique')
                    || str_contains($exception->getMessage(), 'transaksis.idempotency_key')) {
                    // Outside the failed transaction: a fresh read sees the
                    // committed winner, rather than an older InnoDB snapshot.
                    $existing = Transaksi::where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        return $this->replay($existing, $requestHash);
                    }
                    throw $exception;
                }
                // Retry only a generated transaction-number collision, never an
                // unrelated uniqueness failure. The failed transaction rolled back.
                if (! str_contains($exception->getMessage(), 'transaksis_kode_transaksi_unique')
                    && ! str_contains($exception->getMessage(), 'transaksis.kode_transaksi')) {
                    throw $exception;
                }
                if ($attempt === 2) {
                    throw ValidationException::withMessages(['transaksi' => 'Nomor transaksi belum dapat dibuat. Silakan coba kembali.']);
                }
            }
        }

        throw new \LogicException('Sale transaction retry exhausted.');
    }

    private function replay(Transaksi $sale, string $requestHash): Transaksi
    {
        if (! hash_equals((string) $sale->request_hash, $requestHash)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Token sudah dipakai untuk pengajuan lain. Buka form transaksi baru sebelum mengubah keranjang.',
            ]);
        }

        return $sale;
    }
}
