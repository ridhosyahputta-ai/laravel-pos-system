<?php

namespace App\Http\Controllers;

use App\Actions\CreateSale;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Transaksi::query();
        if (! $request->user()->hasRole('admin')) {
            $query->where('user_id', $request->user()->id);
        }
        $transaksis = $query->latest('id')->paginate(10);

        return view('transaksi.index', compact('transaksis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $produks = Produk::where('stok', '>', 0)->orderBy('nama')->orderBy('id')->get();
        $idempotencyKey = old('idempotency_key');
        if (! is_string($idempotencyKey) || ! Str::isUuid($idempotencyKey)) {
            $idempotencyKey = (string) Str::uuid();
        }

        return view('transaksi.create', compact('produks', 'idempotencyKey'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, CreateSale $createSale)
    {
        $integerInput = static function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_int($value) && ! is_string($value)) {
                $fail('Pilihan produk dan jumlah beli harus berupa bilangan bulat.');
            }
        };

        $validated = $request->validate([
            'idempotency_key' => 'bail|required|string|uuid',
            'produk_id' => 'required|array|list|min:1|max:100',
            'produk_id.*' => ['bail', 'required', $integerInput, 'integer', 'min:1', 'max:9223372036854775807'],
            'jumlah' => 'required|array|list|min:1|max:100',
            'jumlah.*' => ['bail', 'required', $integerInput, 'integer', 'min:1', 'max:2147483647'],
        ], [
            'idempotency_key.required' => 'Token pengajuan diperlukan. Muat ulang form transaksi.',
            'idempotency_key.uuid' => 'Token pengajuan tidak valid. Muat ulang form transaksi.',
            'idempotency_key.string' => 'Token pengajuan tidak valid. Muat ulang form transaksi.',
            'produk_id.required' => 'Pilih minimal satu produk.',
            'produk_id.array' => 'Daftar produk tidak valid.',
            'produk_id.max' => 'Maksimal 100 baris produk per transaksi.',
            'produk_id.*.integer' => 'Pilihan produk tidak valid.',
            'produk_id.*.min' => 'Pilihan produk tidak valid.',
            'produk_id.*.max' => 'Pilihan produk tidak valid.',
            'jumlah.required' => 'Isi jumlah beli untuk setiap produk.',
            'jumlah.array' => 'Daftar jumlah beli tidak valid.',
            'jumlah.max' => 'Maksimal 100 baris produk per transaksi.',
            'jumlah.*.integer' => 'Jumlah beli harus berupa bilangan bulat.',
            'jumlah.*.min' => 'Jumlah beli minimal 1.',
            'jumlah.*.max' => 'Jumlah beli melebihi batas yang diizinkan.',
            'produk_id.list' => 'Susunan keranjang tidak valid. Pilih ulang produk.',
            'jumlah.list' => 'Susunan jumlah beli tidak valid. Pilih ulang produk.',
        ]);

        if (count($validated['produk_id']) !== count($validated['jumlah'])) {
            throw ValidationException::withMessages(['jumlah' => 'Setiap produk harus memiliki tepat satu jumlah beli.']);
        }

        try {
            $sale = $createSale->handle($validated['produk_id'], $validated['jumlah'], $validated['idempotency_key'], (int) $request->user()->id);
        } catch (QueryException $exception) {
            report($exception);
            throw ValidationException::withMessages(['transaksi' => 'Transaksi gagal disimpan. Tidak ada stok atau transaksi yang diubah. Silakan coba kembali.']);
        }

        return redirect()->route('transaksi.index')->with('success', $sale->wasRecentlyCreated
            ? 'Transaksi berhasil disimpan.'
            : 'Transaksi ini sudah tersimpan. Stok tidak dikurangi kembali.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaksi $transaksi)
    {
        abort_unless($request->user()->hasRole('admin') || $transaksi->user_id === $request->user()->id, 403);
        $transaksi->load('detail.produk');

        return view('transaksi.show', compact('transaksi'));
    }
}
