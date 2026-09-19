@if ($paginator->hasPages())
    <nav class="pagination-container" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="pagination-info">
            <p>
                Menampilkan
                @if ($paginator->firstItem())
                    <span>{{ $paginator->firstItem() }}</span>
                    sampai
                    <span>{{ $paginator->lastItem() }}</span>
                @else
                    <span>{{ $paginator->count() }}</span>
                @endif
                dari
                <span>{{ $paginator->total() }}</span>
                hasil
            </p>
        </div>

        <div class="pagination-links">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-item pagination-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <x-icon name="chevron-left" size="16" class="pagination-icon" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-item pagination-link" aria-label="{{ __('pagination.previous') }}">
                    <x-icon name="chevron-left" size="16" class="pagination-icon" />
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination-item pagination-ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-item pagination-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-item pagination-link" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-item pagination-link" aria-label="{{ __('pagination.next') }}">
                    <x-icon name="chevron-right" size="16" class="pagination-icon" />
                </a>
            @else
                <span class="pagination-item pagination-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <x-icon name="chevron-right" size="16" class="pagination-icon" />
                </span>
            @endif
        </div>
    </nav>
@endif
