@if ($paginator->hasPages())
    <nav class="lt-pagination-nav" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="lt-pagination-list">
            <li class="lt-pagination-prev {{ $paginator->onFirstPage() ? 'is-disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true">{!! __('pagination.previous') !!}</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">{!! __('pagination.previous') !!}</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="lt-pagination-ellipsis" aria-hidden="true"><span>{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="{{ $page == $paginator->currentPage() ? 'is-current' : '' }}">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li class="lt-pagination-next {{ $paginator->hasMorePages() ? '' : 'is-disabled' }}">
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">{!! __('pagination.next') !!}</a>
                @else
                    <span aria-disabled="true">{!! __('pagination.next') !!}</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
