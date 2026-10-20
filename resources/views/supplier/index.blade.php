<x-master-layout title="Data Supplier" description="Kelola informasi pemasok dan kontak bisnis.">

    <div class="space-y-5">
        <div class="w-full">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />

                <x-master-toolbar title="Daftar Supplier" :total="$suppliers->total()">
                    <a href="{{ route('supplier.create') }}" class="master-btn master-btn-primary">+ Tambah Supplier</a>
                </x-master-toolbar>

                <x-master-table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Supplier</th>
                            <th>Kontak</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $index => $supplier)
                            <tr>
                                <td>{{ $suppliers->firstItem() + $index }}</td>
                                <td>{{ $supplier->nama_supplier }}</td>
                                <td>{{ $supplier->kontak ?? '-' }}</td>
                                <td>{{ $supplier->alamat ?? '-' }}</td>
                                <td><div class="flex items-center gap-2">
                                    <a href="{{ route('supplier.edit', $supplier->id) }}" class="master-action master-action-edit">Edit</a>
                                    <form action="{{ route('supplier.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Hapus supplier? Produk tetap tersimpan, tetapi hubungan supplier akan dikosongkan.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="master-action master-action-delete">Hapus</button>
                                    </form>
                                </div></td>
                            </tr>
                        @empty
                            <x-master-empty colspan="5" module="Supplier" :url="route('supplier.create')" />
                        @endforelse
                    </tbody>
                </x-master-table>
                <div class="mt-5 border-t border-slate-100 pt-4">{{ $suppliers->links('components.master-pagination') }}</div>
            </div>
        </div>
    </div>
</x-master-layout>
