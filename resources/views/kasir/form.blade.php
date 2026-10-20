<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100">{{ $kasir->exists ? 'Edit' : 'Tambah' }} Akun Kasir</h2></x-slot>
<div class="py-12 max-w-3xl mx-auto px-6"><div class="bg-white shadow-sm rounded-lg p-6">
@if($errors->any())<ul class="mb-4 text-red-600">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@if($cabangs->isEmpty())<p class="mb-4">Buat Cabang terlebih dahulu sebelum menyimpan akun Kasir. <a class="text-blue-600" href="{{ route('cabang.create') }}">Tambah Cabang</a></p>@endif
<form method="POST" action="{{ $kasir->exists ? route('kasir.update', $kasir) : route('kasir.store') }}" class="space-y-4">
@csrf @if($kasir->exists) @method('PUT') @endif
<div><label for="name">Nama</label><input id="name" name="name" required maxlength="255" value="{{ old('name', $kasir->name) }}" class="block w-full rounded border-gray-300"></div>
<div><label for="email">Email</label><input id="email" type="email" name="email" required maxlength="255" value="{{ old('email', $kasir->email) }}" class="block w-full rounded border-gray-300"></div>
<div><label for="cabang_id">Cabang</label><select id="cabang_id" name="cabang_id" required class="block w-full rounded border-gray-300"><option value="">Pilih Cabang</option>@foreach($cabangs as $cabang)<option value="{{ $cabang->id }}" @selected(old('cabang_id', $kasir->cabang_id) == $cabang->id)>{{ $cabang->nama_cabang }}</option>@endforeach</select></div>
<div><label for="password">Password {{ $kasir->exists ? '(kosongkan jika tetap)' : '' }}</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" @required(!$kasir->exists) class="block w-full rounded border-gray-300"></div>
<div><label for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="block w-full rounded border-gray-300"></div>
<button @disabled($cabangs->isEmpty()) class="bg-blue-600 text-white rounded px-4 py-2 disabled:opacity-50">Simpan</button>
<a class="text-gray-600" href="{{ route('kasir.index') }}">Batal</a>
</form></div></div></x-app-layout>
