<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Cabang</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                <form action="{{ route('cabang.update', $cabang->id) }}" method="POST" class="space-y-6">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="nama_cabang" :value="__('Nama Cabang')" />
                        <x-text-input id="nama_cabang" class="block mt-1.5 w-full" type="text" name="nama_cabang" maxlength="255" :value="old('nama_cabang', $cabang->nama_cabang)" required />
                        @error('nama_cabang')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="lokasi" :value="__('Lokasi Cabang')" />
                        <x-text-input id="lokasi" class="block mt-1.5 w-full" type="text" name="lokasi" maxlength="255" :value="old('lokasi', $cabang->lokasi)" />
                        @error('lokasi')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-4 pt-6 mt-2 border-t border-gray-100">
                        <x-primary-button>Update</x-primary-button>
                        <a href="{{ route('cabang.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
