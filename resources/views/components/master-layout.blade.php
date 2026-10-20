@props(['title', 'description'])
<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-300 mb-1">Master data</p>
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ $title }}</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $description }}</p>
    </x-slot>
    <div class="master-page min-h-[calc(100vh-12rem)] bg-slate-50 py-6 text-slate-900 sm:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">{{ $slot }}</div>
    </div>
</x-app-layout>
