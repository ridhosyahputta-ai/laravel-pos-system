<x-master-layout title="Edit Pelanggan" description="Simpan informasi pelanggan dengan rapi.">

    <div class="space-y-5">
        <div class="max-w-3xl mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />
                <form action="{{ route('pelanggan.update', $pelanggan->id) }}" method="POST" class="master-form grid gap-5 md:grid-cols-2">
                    @csrf @method('PUT')
                    <div>
                        <x-master-label for="nama" :value="__('Nama Pelanggan')" />
                        <x-master-input id="nama" class="block mt-1.5 w-full" type="text" name="nama" maxlength="255" :value="old('nama', $pelanggan->nama)" required />
                        @error('nama')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="no_hp" :value="__('No HP')" />
                        <x-master-input id="no_hp" class="block mt-1.5 w-full" type="text" name="no_hp" maxlength="20" :value="old('no_hp', $pelanggan->no_hp)" />
                        @error('no_hp')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="alamat" :value="__('Alamat')" />
                        <textarea id="alamat" name="alamat" maxlength="2000" class="master-control block mt-1.5">{{ old('alamat', $pelanggan->alamat) }}</textarea>
                        @error('alamat')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                        <x-master-button>Update</x-master-button>
                        <a href="{{ route('pelanggan.index') }}" class="master-btn master-btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-master-layout>
