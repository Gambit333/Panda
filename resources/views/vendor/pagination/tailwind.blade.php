@if ($paginator->hasPages())
    <nav class="page-links" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="page-btn is-disabled" title="Anterior" aria-disabled="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </span>
        @else
            <a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" title="Anterior" aria-label="Página anterior">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-btn is-gap">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $node)
                    @if (is_string($node))
                        <span class="page-btn is-gap">{{ $node }}</span>
                    @endif

                    @if (is_array($node))
                        @if ($node['isCurrent'])
                            <span class="page-btn is-active" aria-current="page">{{ $node['label'] }}</span>
                        @else
                            <a class="page-btn" href="{{ $node['url'] }}">{{ $node['label'] }}</a>
                        @endif
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" title="Siguiente" aria-label="Página siguiente">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </a>
        @else
            <span class="page-btn is-disabled" title="Siguiente" aria-disabled="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </span>
        @endif
    </nav>
@endif