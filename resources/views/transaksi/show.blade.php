<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Transaksi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                <div class="mb-6 flex justify-between items-start">
                    <div>
                        <p class="text-sm text-gray-500">Kode Transaksi</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $transaksi->kode_transaksi }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Tanggal</p>
                        <p class="text-lg font-semibold text-gray-800"><x-transaction-date :value="$transaksi->created_at" /></p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-3">Nama Produk</th>
                            <th class="border p-3">Jumlah</th>
                            <th class="border p-3">Harga Satuan</th>
                            <th class="border p-3">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transaksi->detail as $item)
                            <tr>
                                <td class="border p-3">{{ $item->produk->nama }}</td>
                                <td class="border p-3">{{ $item->jumlah }}</td>
                                <td class="border p-3">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                <td class="border p-3">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="border p-3 text-center text-gray-500">Tidak ada rincian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="text-xl font-bold text-gray-800">Rp {{ number_format($transaksi->total, 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-6 mt-4 border-t border-gray-100">
                    <a href="{{ route('transaksi.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
