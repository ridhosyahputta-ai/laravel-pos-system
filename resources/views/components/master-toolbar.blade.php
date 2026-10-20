@props(['title', 'total'])
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div><h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3><p class="mt-1 text-sm text-slate-500">{{ number_format($total, 0, ',', '.') }} data tersimpan</p></div>
    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
</div>
