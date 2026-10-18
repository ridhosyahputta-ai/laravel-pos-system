@props(['value'])

@if ($value)
    @php($localDate = $value->copy()->timezone('Asia/Jakarta'))
    <time datetime="{{ $localDate->toIso8601String() }}">{{ $localDate->format('d/m/Y H:i') }} WIB</time>
@else
    <span>—</span>
@endif
