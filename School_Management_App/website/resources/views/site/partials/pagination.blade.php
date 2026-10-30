@if ($paginator->hasPages())
<nav class="pager" aria-label="Pages">
    @if ($paginator->onFirstPage())<span class="pg disabled">@icon('chevron-left','ic',18)</span>@else<a class="pg" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">@icon('chevron-left','ic',18)</a>@endif
    @foreach ($elements as $element)
        @if (is_string($element))<span class="pg disabled">{{ $element }}</span>@endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())<span class="pg is-active" aria-current="page">{{ $page }}</span>@else<a class="pg" href="{{ $url }}">{{ $page }}</a>@endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())<a class="pg" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">@icon('chevron-right','ic',18)</a>@else<span class="pg disabled">@icon('chevron-right','ic',18)</span>@endif
</nav>
@endif
