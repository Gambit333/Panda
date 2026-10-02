@if ($paginator->hasPages())
    @php
        // Solo números entre las flechas: todas las páginas si son pocas, o una
        // ventana de 5 alrededor de la actual si son muchas (sin puntos suspensivos).
        $total = $paginator->lastPage();
        $actual = $paginator->currentPage();

        if ($total <= 7) {
            $paginas = range(1, $total);
        } else {
            $inicio = max(1, $actual - 2);
            $fin = min($total, $inicio + 4);
            $inicio = max(1, $fin - 4);
            $paginas = range($inicio, $fin);
        }
    @endphp

    <nav class="page-links" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="page-btn is-disabled" aria-disabled="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </span>
        @else
            <a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </a>
        @endif

        @foreach ($paginas as $numero)
            @if ($numero === $actual)
                <span class="page-btn is-active" aria-current="page">{{ $numero }}</span>
            @else
                <a class="page-btn" href="{{ $paginator->url($numero) }}">{{ $numero }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </a>
        @else
            <span class="page-btn is-disabled" aria-disabled="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </span>
        @endif
    </nav>
@endif