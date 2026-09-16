<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Petugas;
use App\Models\Produk;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProduk = Produk::count();
        $totalKategori = Kategori::count();
        $totalPelanggan = Pelanggan::count();
        $totalCabang = Cabang::count();
        $totalPetugas = Petugas::count();

        $stokMenipis = Produk::where('stok', '<', 10)->orderBy('stok')->get();

        return view('dashboard', compact(
            'totalProduk',
            'totalKategori',
            'totalPelanggan',
            'totalCabang',
            'totalPetugas',
            'stokMenipis'
        ));
    }
}
