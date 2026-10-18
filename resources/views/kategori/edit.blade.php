<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Kategori</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                <form action="{{ route('kategori.update', $kategori->id) }}" method="POST" class="space-y-6">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="nama_kategori" :value="__('Nama Kategori')" />
                        <x-text-input id="nama_kategori" class="block mt-1.5 w-full" type="text" name="nama_kategori" :value="old('nama_kategori', $kategori->nama_kategori)" required />
                        @error('nama_kategori')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="keterangan" :value="__('Keterangan')" />
                        <textarea id="keterangan" name="keterangan" rows="4" maxlength="2000" class="block mt-1.5 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm">{{ old('keterangan', $kategori->keterangan) }}</textarea>
                        @error('keterangan')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-4 pt-6 mt-2 border-t border-gray-100">
                        <x-primary-button>Update</x-primary-button>
                        <a href="{{ route('kategori.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
