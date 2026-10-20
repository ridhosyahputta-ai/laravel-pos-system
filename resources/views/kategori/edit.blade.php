<x-master-layout title="Edit Kategori" description="Susun kategori agar katalog lebih mudah dikelola.">

    <div class="space-y-5">
        <div class="max-w-3xl mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />
                <form action="{{ route('kategori.update', $kategori->id) }}" method="POST" class="master-form grid gap-5 md:grid-cols-2">
                    @csrf @method('PUT')
                    <div>
                        <x-master-label for="nama_kategori" :value="__('Nama Kategori')" />
                        <x-master-input id="nama_kategori" class="block mt-1.5 w-full" type="text" name="nama_kategori" :value="old('nama_kategori', $kategori->nama_kategori)" required />
                        @error('nama_kategori')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="keterangan" :value="__('Keterangan')" />
                        <textarea id="keterangan" name="keterangan" rows="4" maxlength="2000" class="master-control block mt-1.5">{{ old('keterangan', $kategori->keterangan) }}</textarea>
                        @error('keterangan')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                        <x-master-button>Update</x-master-button>
                        <a href="{{ route('kategori.index') }}" class="master-btn master-btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-master-layout>
