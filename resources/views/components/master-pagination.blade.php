@if($paginator->hasPages())
<nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
    <p class="text-slate-500">Menampilkan {{ $paginator->firstItem() }}?{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data</p>
    <div class="flex flex-wrap gap-1">
        @if($paginator->onFirstPage())<span aria-disabled="true" class="master-page-disabled">Sebelumnya</span>@else<a rel="prev" class="master-page-link" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>@endif
        @foreach($elements as $element)
            @if(is_string($element))<span class="master-page-disabled">{{ $element }}</span>@endif
            @if(is_array($element))@foreach($element as $page => $url)
                @if($page == $paginator->currentPage())<span aria-current="page" class="master-page-current">{{ $page }}</span>@else<a class="master-page-link" aria-label="Halaman {{ $page }}" href="{{ $url }}">{{ $page }}</a>@endif
            @endforeach @endif
        @endforeach
        @if($paginator->hasMorePages())<a rel="next" class="master-page-link" href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>@else<span aria-disabled="true" class="master-page-disabled">Berikutnya</span>@endif
    </div>
</nav>
@endif
