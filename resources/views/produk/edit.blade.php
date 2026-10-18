<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Produk</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                <form action="{{ route('produk.update', $produk->id) }}" method="POST" class="space-y-6">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="nama" :value="__('Nama Produk')" />
                        <x-text-input id="nama" class="block mt-1.5 w-full" type="text" name="nama" :value="old('nama', $produk->nama)" required />
                        @error('nama')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="kode" :value="__('Kode Produk')" />
                        <x-text-input id="kode" class="block mt-1.5 w-full" type="text" name="kode" :value="old('kode', $produk->kode)" required />
                        @error('kode')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="harga" :value="__('Harga')" />
                        <x-text-input id="harga" class="block mt-1.5 w-full" type="number" min="0" max="2147483647" step="1" name="harga" :value="old('harga', $produk->harga)" required />
                        @error('harga')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="stok" :value="__('Stok')" />
                        <x-text-input id="stok" class="block mt-1.5 w-full" type="number" min="0" max="2147483647" step="1" name="stok" :value="old('stok', $produk->stok)" required />
                        @error('stok')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="kategori_id" :value="__('Kategori')" />
                        <select id="kategori_id" name="kategori_id" class="block mt-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                        <x-input-label for="supplier_id" :value="__('Supplier')" />
                        <select id="supplier_id" name="supplier_id" class="block mt-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                        <x-input-label for="keterangan" :value="__('Keterangan')" />
                        <textarea id="keterangan" name="keterangan" rows="4" maxlength="2000" class="block mt-1.5 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm">{{ old('keterangan', $produk->keterangan) }}</textarea>
                        @error('keterangan')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-4 pt-6 mt-2 border-t border-gray-100">
                        <x-primary-button>Update</x-primary-button>
                        <a href="{{ route('produk.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
