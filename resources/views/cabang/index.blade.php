<x-master-layout title="Data Cabang" description="Kelola informasi lokasi operasional Runway.">

    <div class="space-y-5">
        <div class="w-full">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />

                <x-master-toolbar title="Daftar Cabang" :total="$cabangs->total()">
                    <a href="{{ route('cabang.create') }}" class="master-btn master-btn-primary">+ Tambah Cabang</a>
                </x-master-toolbar>

                <x-master-table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Cabang</th>
                            <th>Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cabangs as $index => $item)
                            <tr>
                                <td>{{ $cabangs->firstItem() + $index }}</td>
                                <td>{{ $item->nama_cabang }}</td>
                                <td>{{ $item->lokasi ?? '-' }}</td>
                                <td><div class="flex items-center gap-2">
                                    <a href="{{ route('cabang.edit', $item->id) }}" class="master-action master-action-edit">Edit</a>
                                    <form action="{{ route('cabang.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="master-action master-action-delete">Hapus</button>
                                    </form>
                                </div></td>
                            </tr>
                        @empty
                            <x-master-empty colspan="4" module="Cabang" :url="route('cabang.create')" />
                        @endforelse
                    </tbody>
                </x-master-table>
                <div class="mt-5 border-t border-slate-100 pt-4">{{ $cabangs->links('components.master-pagination') }}</div>
            </div>
        </div>
    </div>
</x-master-layout>