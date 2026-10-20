<x-master-layout title="Tambah Cabang" description="Kelola informasi lokasi operasional Runway.">

    <div class="space-y-5">
        <div class="max-w-3xl mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <x-master-feedback />
                <form action="{{ route('cabang.store') }}" method="POST" class="master-form grid gap-5 md:grid-cols-2">
                    @csrf
                    <div>
                        <x-master-label for="nama_cabang" :value="__('Nama Cabang')" />
                        <x-master-input id="nama_cabang" class="block mt-1.5 w-full" type="text" name="nama_cabang" maxlength="255" :value="old('nama_cabang')" required />
                        @error('nama_cabang')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-master-label for="lokasi" :value="__('Lokasi Cabang')" />
                        <x-master-input id="lokasi" class="block mt-1.5 w-full" type="text" name="lokasi" maxlength="255" :value="old('lokasi')" />
                        @error('lokasi')
                            <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                        <x-master-button>Simpan</x-master-button>
                        <a href="{{ route('cabang.index') }}" class="master-btn master-btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-master-layout>
