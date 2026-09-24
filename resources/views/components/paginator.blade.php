@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="filters" style="margin-top:var(--s-12)" aria-label="Σελιδοποίηση">
        @if ($paginator->onFirstPage())
            <span class="chip" aria-disabled="true" style="opacity:.45;cursor:default">‹ Προηγούμενα</span>
        @else
            <a class="chip" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Προηγούμενα</a>
        @endif

        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            <a class="chip {{ $page == $paginator->currentPage() ? 'active' : '' }}" href="{{ $url }}"
               @if ($page == $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="chip" href="{{ $paginator->nextPageUrl() }}" rel="next">Επόμενα ›</a>
        @else
            <span class="chip" aria-disabled="true" style="opacity:.45;cursor:default">Επόμενα ›</span>
        @endif
    </nav>
@endif
