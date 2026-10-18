@props(['textClass' => 'text-gray-900 dark:text-gray-100'])

<span {{ $attributes->merge(['class' => 'inline-flex items-baseline font-bold tracking-tight']) }}>
    <span class="{{ $textClass }}">Run</span><span class="text-blue-600">way</span>
</span>
