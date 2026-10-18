<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Transaksi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('transaksi.store') }}" method="POST">
                    @csrf

                    <table class="w-full text-left border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-3">Pilih</th>
                                <th class="border p-3">Nama Produk</th>
                                <th class="border p-3">Harga</th>
                                <th class="border p-3">Stok</th>
                                <th class="border p-3">Jumlah Beli</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($produks as $produk)
                                <tr>
                                    <td class="border p-3">
                                        <input type="checkbox"
                                               name="produk_id[]"
                                               value="{{ $produk->id }}"
                                               onchange="toggleJumlah(this)"
                                               data-target="jumlah-{{ $produk->id }}">
                                    </td>
                                    <td class="border p-3">{{ $produk->nama }}</td>
                                    <td class="border p-3">{{ number_format($produk->harga, 0, ',', '.') }}</td>
                                    <td class="border p-3">{{ $produk->stok }}</td>
                                    <td class="border p-3">
                                        <input type="number"
                                               id="jumlah-{{ $produk->id }}"
                                               name="jumlah[]"
                                               min="1"
                                               max="{{ $produk->stok }}"
                                               value="1"
                                               disabled
                                               class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="border p-3 text-center text-gray-500">Tidak ada produk dengan stok tersedia.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="flex items-center gap-4 pt-6 mt-4 border-t border-gray-100">
                        <x-primary-button>Proses Transaksi</x-primary-button>
                        <a href="{{ route('transaksi.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleJumlah(checkbox) {
            const input = document.getElementById(checkbox.dataset.target);
            input.disabled = !checkbox.checked;
        }
    </script>
</x-app-layout>
