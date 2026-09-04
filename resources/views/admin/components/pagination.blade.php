@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $windowStart = max(1, $currentPage - 2);
    $windowEnd = min($lastPage, $currentPage + 2);
    $label = $itemLabel ?? 'records';
@endphp
<div class="admin-pagination-bar">
    <p class="admin-pagination-summary">
        @if($paginator->total())
            Showing <strong>{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}</strong>
            of <strong>{{ number_format($paginator->total()) }}</strong> {{ $label }}
        @else
            No {{ $label }} found
        @endif
    </p>
    @if($paginator->hasPages())
        <nav class="pagination admin-pagination" aria-label="{{ ucfirst($label) }} pagination">
            <ul>
                <li class="prev {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    @if($paginator->onFirstPage())<span aria-hidden="true">← Previous</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Previous</a>@endif
                </li>
                @if($windowStart > 1)
                    <li><a href="{{ $paginator->url(1) }}">1</a></li>
                    @if($windowStart > 2)<li class="disabled"><span aria-hidden="true">…</span></li>@endif
                @endif
                @for($page = $windowStart; $page <= $windowEnd; $page++)
                    <li class="{{ $page === $currentPage ? 'active' : '' }}">
                        @if($page === $currentPage)<span aria-current="page">{{ $page }}</span>@else<a href="{{ $paginator->url($page) }}">{{ $page }}</a>@endif
                    </li>
                @endfor
                @if($windowEnd < $lastPage)
                    @if($windowEnd < $lastPage - 1)<li class="disabled"><span aria-hidden="true">…</span></li>@endif
                    <li><a href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a></li>
                @endif
                <li class="next {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Next →</a>@else<span aria-hidden="true">Next →</span>@endif
                </li>
            </ul>
        </nav>
    @endif
</div>
