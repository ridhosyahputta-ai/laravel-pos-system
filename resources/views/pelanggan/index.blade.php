<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Pelanggan</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
                @endif

                <div class="mb-4">
                    <a href="{{ route('pelanggan.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">+ Tambah Pelanggan</a>
                </div>

                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-3">No</th>
                            <th class="border p-3">Nama Pelanggan</th>
                            <th class="border p-3">No HP</th>
                            <th class="border p-3">Alamat</th>
                            <th class="border p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pelanggans as $index => $item)
                            <tr>
                                <td class="border p-3">{{ $pelanggans->firstItem() + $index }}</td>
                                <td class="border p-3">{{ $item->nama }}</td>
                                <td class="border p-3">{{ $item->no_hp ?? '-' }}</td>
                                <td class="border p-3">{{ $item->alamat ?? '-' }}</td>
                                <td class="border p-3 flex gap-3">
                                    <a href="{{ route('pelanggan.edit', $item->id) }}" class="text-blue-600 hover:underline">Edit</a>
                                    <form action="{{ route('pelanggan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="border p-3 text-center text-gray-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $pelanggans->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>