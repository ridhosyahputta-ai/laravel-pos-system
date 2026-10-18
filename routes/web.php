<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/produk/export/pdf', [ProdukController::class, 'downloadPdf'])->name('produk.pdf');

    // Route Resource CRUD
    Route::resource('produk', ProdukController::class)->except('show');
    Route::resource('kategori', KategoriController::class)->except('show');
    Route::resource('pelanggan', PelangganController::class)->except('show');
    Route::resource('cabang', CabangController::class)->except('show');
    Route::resource('petugas', PetugasController::class)->parameters(['petugas' => 'petugas'])->except('show');
    Route::resource('supplier', SupplierController::class)->except('show');

    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/create', [TransaksiController::class, 'create'])->name('transaksi.create');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('transaksi.show');
});

require __DIR__.'/auth.php';
