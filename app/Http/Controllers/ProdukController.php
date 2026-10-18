<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $produks = Produk::with(['kategori', 'supplier'])->latest()->paginate(10);
        return view('produk.index', compact('produks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kategoris = Kategori::all();
        $suppliers = Supplier::all();
        return view('produk.create', compact('kategoris', 'suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:255|unique:produks,kode',
            'harga' => 'required|integer|min:0|max:2147483647',
            'stok' => 'required|integer|min:0|max:2147483647',
            'kategori_id' => 'nullable|integer|exists:kategoris,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'keterangan' => 'nullable|string|max:2000',
        ]);

        Produk::create($validated);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Produk $produk)
    {
        $kategoris = Kategori::all();
        $suppliers = Supplier::all();
        return view('produk.edit', compact('produk', 'kategoris', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => ['required', 'string', 'max:255', Rule::unique('produks', 'kode')->ignore($produk->id)],
            'harga' => 'required|integer|min:0|max:2147483647',
            'stok' => 'required|integer|min:0|max:2147483647',
            'kategori_id' => 'nullable|integer|exists:kategoris,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'keterangan' => 'nullable|string|max:2000',
        ]);

        $produk->update($validated);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Produk $produk)
    {
        $message = 'Produk tidak dapat dihapus karena sudah digunakan dalam transaksi.';

        if ($produk->transaksiDetails()->exists()) {
            return redirect()->route('produk.index')->with('error', $message);
        }

        try {
            $produk->delete();
        } catch (QueryException $exception) {
            // A transaction may have referenced this product after the initial check.
            if (($exception->errorInfo[0] ?? null) === '23000' && $produk->transaksiDetails()->exists()) {
                return redirect()->route('produk.index')->with('error', $message);
            }

            throw $exception;
        }

        return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function downloadPdf()
    {
        $produks = Produk::with('kategori')->get();
        $pdf = Pdf::loadView('produk_pdf', compact('produks'));
        return $pdf->download('data-produk.pdf');
    }
}
