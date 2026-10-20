<x-master-layout title="Edit Produk" description="Kelola katalog, harga, dan stok global produk.">

    <div class="space-y-5">
        <div class="max-w-4xl mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />
                <form action="{{ route('produk.update', $produk->id) }}" method="POST" class="master-form grid gap-5 md:grid-cols-2">
                    @csrf @method('PUT')
                    <div>
                        <x-master-label for="nama" :value="__('Nama Produk')" />
                        <x-master-input id="nama" class="block mt-1.5 w-full" type="text" name="nama" :value="old('nama', $produk->nama)" required />
                        @error('nama')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="kode" :value="__('Kode Produk')" />
                        <x-master-input id="kode" class="block mt-1.5 w-full" type="text" name="kode" :value="old('kode', $produk->kode)" required />
                        @error('kode')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="harga" :value="__('Harga')" />
                        <x-master-input id="harga" class="block mt-1.5 w-full" type="number" min="0" max="2147483647" step="1" name="harga" :value="old('harga', $produk->harga)" required />
                        @error('harga')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="stok" :value="__('Stok')" />
                        <x-master-input id="stok" class="block mt-1.5 w-full" type="number" min="0" max="2147483647" step="1" name="stok" :value="old('stok', $produk->stok)" required />
                        @error('stok')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="kategori_id" :value="__('Kategori')" />
                        <select id="kategori_id" name="kategori_id" class="master-control block mt-1.5">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($kategoris as $kategori)
                                <option value="{{ $kategori->id }}" {{ old('kategori_id', $produk->kategori_id) == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama_kategori }}</option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="supplier_id" :value="__('Supplier')" />
                        <select id="supplier_id" name="supplier_id" class="master-control block mt-1.5">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $produk->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->nama_supplier }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="keterangan" :value="__('Keterangan')" />
                        <textarea id="keterangan" name="keterangan" rows="4" maxlength="2000" class="master-control block mt-1.5">{{ old('keterangan', $produk->keterangan) }}</textarea>
                        @error('keterangan')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                        <x-master-button>Update</x-master-button>
                        <a href="{{ route('produk.index') }}" class="master-btn master-btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-master-layout>
