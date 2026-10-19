<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl">Akun Kasir</h2></x-slot>
<div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white shadow-sm rounded-lg p-6">
@if(session('success'))<p class="mb-4 text-green-700">{{ session('success') }}</p>@endif
<a href="{{ route('kasir.create') }}" class="text-blue-600">+ Tambah Kasir</a>
<div class="overflow-x-auto mt-4"><table class="w-full text-left"><thead><tr><th>Nama</th><th>Email</th><th>Cabang</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($kasirs as $kasir)<tr class="border-t"><td class="py-3">{{ $kasir->name }}</td><td>{{ $kasir->email }}</td><td>{{ $kasir->cabang?->nama_cabang ?? '-' }}</td><td>{{ $kasir->is_active ? 'Aktif' : 'Nonaktif' }}</td><td>
<a class="text-blue-600" href="{{ route('kasir.edit', $kasir) }}">Edit</a>
@if($kasir->is_active)<form method="POST" action="{{ route('kasir.deactivate', $kasir) }}" onsubmit="return confirm('Nonaktifkan akun Kasir?')">@csrf @method('PATCH')<button class="text-red-600">Nonaktifkan</button></form>@endif
</td></tr>@empty<tr><td colspan="5" class="py-4">Belum ada akun Kasir.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $kasirs->links() }}</div>
</div></div></x-app-layout>
