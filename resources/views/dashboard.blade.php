<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-6">
                    <p class="text-sm font-medium text-gray-500">Total Produk</p>
                    <p class="mt-1.5 text-3xl font-semibold text-gray-800">{{ $totalProduk }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-6">
                    <p class="text-sm font-medium text-gray-500">Total Kategori</p>
                    <p class="mt-1.5 text-3xl font-semibold text-gray-800">{{ $totalKategori }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-6">
                    <p class="text-sm font-medium text-gray-500">Total Pelanggan</p>
                    <p class="mt-1.5 text-3xl font-semibold text-gray-800">{{ $totalPelanggan }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-6">
                    <p class="text-sm font-medium text-gray-500">Total Cabang</p>
                    <p class="mt-1.5 text-3xl font-semibold text-gray-800">{{ $totalCabang }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-6">
                    <p class="text-sm font-medium text-gray-500">Total Petugas</p>
                    <p class="mt-1.5 text-3xl font-semibold text-gray-800">{{ $totalPetugas }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Stok Menipis</h3>

                @if ($stokMenipis->isEmpty())
                    <p class="text-gray-500">Semua stok aman.</p>
                @else
                    <table class="w-full text-left border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-3">Kode</th>
                                <th class="border p-3">Nama</th>
                                <th class="border p-3">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stokMenipis as $produk)
                                <tr>
                                    <td class="border p-3">{{ $produk->kode }}</td>
                                    <td class="border p-3">{{ $produk->nama }}</td>
                                    <td class="border p-3 font-semibold text-red-600">{{ $produk->stok }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
