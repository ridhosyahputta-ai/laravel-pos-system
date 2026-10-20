<x-master-layout title="Data Pelanggan" description="Simpan informasi pelanggan dengan rapi.">

    <div class="space-y-5">
        <div class="w-full">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />

                <x-master-toolbar title="Daftar Pelanggan" :total="$pelanggans->total()">
                    <a href="{{ route('pelanggan.create') }}" class="master-btn master-btn-primary">+ Tambah Pelanggan</a>
                </x-master-toolbar>

                <x-master-table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pelanggan</th>
                            <th>No HP</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pelanggans as $index => $item)
                            <tr>
                                <td>{{ $pelanggans->firstItem() + $index }}</td>
                                <td>{{ $item->nama }}</td>
                                <td>{{ $item->no_hp ?? '-' }}</td>
                                <td>{{ $item->alamat ?? '-' }}</td>
                                <td><div class="flex items-center gap-2">
                                    <a href="{{ route('pelanggan.edit', $item->id) }}" class="master-action master-action-edit">Edit</a>
                                    <form action="{{ route('pelanggan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="master-action master-action-delete">Hapus</button>
                                    </form>
                                </div></td>
                            </tr>
                        @empty
                            <x-master-empty colspan="5" module="Pelanggan" :url="route('pelanggan.create')" />
                        @endforelse
                    </tbody>
                </x-master-table>
                <div class="mt-5 border-t border-slate-100 pt-4">{{ $pelanggans->links('components.master-pagination') }}</div>
            </div>
        </div>
    </div>
</x-master-layout>