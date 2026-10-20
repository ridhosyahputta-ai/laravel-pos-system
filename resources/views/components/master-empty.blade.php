@props(['colspan', 'module', 'url'])
<tr><td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
    <p class="font-semibold text-slate-700">Belum ada data.</p>
    <p class="mt-1 text-sm text-slate-500">Tambahkan {{ strtolower($module) }} pertama untuk mulai mengelola data.</p>
    <a href="{{ $url }}" class="master-btn master-btn-secondary mt-4">+ Tambah {{ $module }}</a>
</td></tr>
