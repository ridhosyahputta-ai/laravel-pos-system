<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transaksis = Transaksi::latest()->paginate(10);
        return view('transaksi.index', compact('transaksis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $produks = Produk::where('stok', '>', 0)->get();
        return view('transaksi.create', compact('produks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'produk_id' => 'required|array|min:1',
            'produk_id.*' => 'required|exists:produks,id',
            'jumlah' => 'required|array',
            'jumlah.*' => 'required|integer|min:1',
        ]);

        $transaksi = DB::transaction(function () use ($validated) {
            $transaksi = Transaksi::create([
                'kode_transaksi' => 'TRX-' . now()->format('Ymd-His'),
                'total' => 0,
            ]);

            $total = 0;

            foreach ($validated['produk_id'] as $index => $produkId) {
                $jumlah = $validated['jumlah'][$index];
                $produk = Produk::lockForUpdate()->findOrFail($produkId);

                if ($jumlah > $produk->stok) {
                    throw ValidationException::withMessages([
                        'jumlah' => "Stok produk {$produk->nama} tidak mencukupi. Sisa stok: {$produk->stok}.",
                    ]);
                }

                $subtotal = $produk->harga * $jumlah;

                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $produk->id,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $produk->harga,
                    'subtotal' => $subtotal,
                ]);

                $produk->decrement('stok', $jumlah);

                $total += $subtotal;
            }

            $transaksi->update(['total' => $total]);

            return $transaksi;
        });

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        $transaksi->load('detail.produk');
        return view('transaksi.show', compact('transaksi'));
    }
}
