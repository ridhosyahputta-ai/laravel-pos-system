<x-master-layout title="Data Produk" description="Kelola katalog, harga, dan stok global produk.">

    <div class="space-y-5">
        <div class="w-full">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />

               <x-master-toolbar title="Daftar Produk" :total="$produks->total()">
    <a href="{{ route('produk.create') }}" class="master-btn master-btn-primary">+ Tambah Produk</a>
    <a href="{{ route('produk.pdf') }}" class="master-btn master-btn-secondary">Export PDF</a>
</x-master-toolbar>

                <x-master-table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Supplier</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($produks as $index => $produk)
                            <tr>
                                <td>{{ $produks->firstItem() + $index }}</td>
                                <td>{{ $produk->kode }}</td>
                                <td>{{ $produk->nama }}</td>
                                <td>{{ $produk->kategori->nama_kategori ?? '-' }}</td>
                                <td>{{ $produk->supplier->nama_supplier ?? '-' }}</td>
                                <td>{{ $produk->harga }}</td>
                                <td>{{ $produk->stok }}</td>
                                <td>{{ $produk->keterangan ?? '-' }}</td>
                                <td><div class="flex items-center gap-2">
                                    <a href="{{ route('produk.edit', $produk->id) }}" class="master-action master-action-edit">Edit</a>
                                    <form action="{{ route('produk.destroy', $produk->id) }}" method="POST" onsubmit="return confirm('Hapus data?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="master-action master-action-delete">Hapus</button>
                                    </form>
                                </div></td>
                            </tr>
                        @empty
                            <x-master-empty colspan="9" module="Produk" :url="route('produk.create')" />
                        @endforelse
                    </tbody>
                </x-master-table>
                <div class="mt-5 border-t border-slate-100 pt-4">{{ $produks->links('components.master-pagination') }}</div>
            </div>
        </div>
    </div>
</x-master-layout>
