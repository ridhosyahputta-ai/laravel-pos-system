<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Produk</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
                @endif

               <div class="mb-4 flex gap-3">
    <a href="{{ route('produk.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">+ Tambah Produk</a>
    <a href="{{ route('produk.pdf') }}" class="text-white px-4 py-2 rounded-lg" style="background-color: #16a34a;" onmouseover="this.style.backgroundColor='#15803d'" onmouseout="this.style.backgroundColor='#16a34a'">Export PDF</a>
</div>

                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-3">No</th>
                            <th class="border p-3">Kode</th>
                            <th class="border p-3">Nama</th>
                            <th class="border p-3">Kategori</th>
                            <th class="border p-3">Supplier</th>
                            <th class="border p-3">Harga</th>
                            <th class="border p-3">Stok</th>
                            <th class="border p-3">Keterangan</th>
                            <th class="border p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produks as $index => $produk)
                            <tr>
                                <td class="border p-3">{{ $produks->firstItem() + $index }}</td>
                                <td class="border p-3">{{ $produk->kode }}</td>
                                <td class="border p-3">{{ $produk->nama }}</td>
                                <td class="border p-3">{{ $produk->kategori->nama_kategori ?? '-' }}</td>
                                <td class="border p-3">{{ $produk->supplier->nama_supplier ?? '-' }}</td>
                                <td class="border p-3">{{ $produk->harga }}</td>
                                <td class="border p-3">{{ $produk->stok }}</td>
                                <td class="border p-3">{{ $produk->keterangan ?? '-' }}</td>
                                <td class="border p-3 flex gap-3">
                                    <a href="{{ route('produk.edit', $produk->id) }}" class="text-blue-600 hover:underline">Edit</a>
                                    <form action="{{ route('produk.destroy', $produk->id) }}" method="POST" onsubmit="return confirm('Hapus data?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="border p-3 text-center text-gray-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $produks->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
