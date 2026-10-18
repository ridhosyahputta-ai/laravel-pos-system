<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Pelanggan</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                <form action="{{ route('pelanggan.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <x-input-label for="nama" :value="__('Nama Pelanggan')" />
                        <x-text-input id="nama" class="block mt-1.5 w-full" type="text" name="nama" maxlength="255" :value="old('nama')" required />
                        @error('nama')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="no_hp" :value="__('No HP')" />
                        <x-text-input id="no_hp" class="block mt-1.5 w-full" type="text" name="no_hp" maxlength="20" :value="old('no_hp')" />
                        @error('no_hp')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="alamat" :value="__('Alamat')" />
                        <textarea id="alamat" name="alamat" maxlength="2000" class="block mt-1.5 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('alamat') }}</textarea>
                        @error('alamat')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-4 pt-6 mt-2 border-t border-gray-100">
                        <x-primary-button>Simpan</x-primary-button>
                        <a href="{{ route('pelanggan.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
