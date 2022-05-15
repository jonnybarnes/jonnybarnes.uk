@if ($paginator->hasPages())
    <nav role="navigation">
        <div>
            @if ($paginator->onFirstPage())
                <span>
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span>
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>
    </nav>
@endif
