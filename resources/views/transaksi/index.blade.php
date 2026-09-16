<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Transaksi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-6">
                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
                @endif

                <div class="mb-4">
                    <a href="{{ route('transaksi.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">+ Buat Transaksi</a>
                </div>

                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-3">No</th>
                            <th class="border p-3">Kode Transaksi</th>
                            <th class="border p-3">Total</th>
                            <th class="border p-3">Tanggal</th>
                            <th class="border p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transaksis as $index => $transaksi)
                            <tr>
                                <td class="border p-3">{{ $transaksis->firstItem() + $index }}</td>
                                <td class="border p-3">{{ $transaksi->kode_transaksi }}</td>
                                <td class="border p-3">Rp {{ number_format($transaksi->total, 0, ',', '.') }}</td>
                                <td class="border p-3">{{ $transaksi->created_at->format('d/m/Y H:i') }}</td>
                                <td class="border p-3">
                                    <a href="{{ route('transaksi.show', $transaksi->id) }}" class="text-blue-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="border p-3 text-center text-gray-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $transaksis->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
