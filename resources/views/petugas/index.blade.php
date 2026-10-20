<x-master-layout title="Data Petugas" description="Kelola data petugas. Data ini terpisah dari akun login.">

    <div class="space-y-5">
        <div class="w-full">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />

                <x-master-toolbar title="Daftar Petugas" :total="$petugas->total()">
                    <a href="{{ route('petugas.create') }}" class="master-btn master-btn-primary">+ Tambah Petugas</a>
                </x-master-toolbar>

                <x-master-table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($petugas as $index => $item)
                            <tr>
                                <td>{{ $petugas->firstItem() + $index }}</td>
                                <td>{{ $item->nama }}</td>
                                <td class="border p-3 font-semibold text-gray-700">{{ $item->username }}</td>
                                <td>{{ $item->role }}</td>
                                <td><div class="flex items-center gap-2">
                                    <a href="{{ route('petugas.edit', $item->id) }}" class="master-action master-action-edit">Edit</a>
                                    <form action="{{ route('petugas.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="master-action master-action-delete">Hapus</button>
                                    </form>
                                </div></td>
                            </tr>
                        @empty
                            <x-master-empty colspan="5" module="Petugas" :url="route('petugas.create')" />
                        @endforelse
                    </tbody>
                </x-master-table>
                <div class="mt-5 border-t border-slate-100 pt-4">{{ $petugas->links('components.master-pagination') }}</div>
            </div>
        </div>
    </div>
</x-master-layout>