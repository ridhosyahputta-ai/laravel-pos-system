<x-master-layout title="Tambah Supplier" description="Kelola informasi pemasok dan kontak bisnis.">

    <div class="space-y-5">
        <div class="max-w-3xl mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />
                <form action="{{ route('supplier.store') }}" method="POST" class="master-form grid gap-5 md:grid-cols-2">
                    @csrf
                    <div>
                        <x-master-label for="nama_supplier" :value="__('Nama Supplier')" />
                        <x-master-input id="nama_supplier" class="block mt-1.5 w-full" type="text" name="nama_supplier" maxlength="255" :value="old('nama_supplier')" required />
                        @error('nama_supplier')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="kontak" :value="__('Kontak')" />
                        <x-master-input id="kontak" class="block mt-1.5 w-full" type="text" name="kontak" maxlength="255" :value="old('kontak')" />
                        @error('kontak')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="alamat" :value="__('Alamat')" />
                        <textarea id="alamat" name="alamat" maxlength="2000" rows="4" class="master-control block mt-1.5">{{ old('alamat') }}</textarea>
                        @error('alamat')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                        <x-master-button>Simpan</x-master-button>
                        <a href="{{ route('supplier.index') }}" class="master-btn master-btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-master-layout>
