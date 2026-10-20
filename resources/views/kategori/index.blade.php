<x-master-layout title="Data Kategori" description="Susun kategori agar katalog lebih mudah dikelola.">

    <div class="space-y-5">
        <div class="w-full">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />

                <x-master-toolbar title="Daftar Kategori" :total="$kategoris->total()">
                    <a href="{{ route('kategori.create') }}" class="master-btn master-btn-primary">+ Tambah Kategori</a>
                </x-master-toolbar>

                <x-master-table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kategoris as $index => $item)
                            <tr>
                                <td>{{ $kategoris->firstItem() + $index }}</td>
                                <td>{{ $item->nama_kategori }}</td>
                                <td>{{ $item->keterangan ?? '-' }}</td>
                                <td><div class="flex items-center gap-2">
                                    <a href="{{ route('kategori.edit', $item->id) }}" class="master-action master-action-edit">Edit</a>
                                    <form action="{{ route('kategori.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="master-action master-action-delete">Hapus</button>
                                    </form>
                                </div></td>
                            </tr>
                        @empty
                            <x-master-empty colspan="4" module="Kategori" :url="route('kategori.create')" />
                        @endforelse
                    </tbody>
                </x-master-table>
                <div class="mt-5 border-t border-slate-100 pt-4">{{ $kategoris->links('components.master-pagination') }}</div>
            </div>
        </div>
    </div>
</x-master-layout>