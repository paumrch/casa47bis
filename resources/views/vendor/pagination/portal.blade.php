@if ($paginator->hasPages())
    {{--
        Paginación accesible: es una lista de enlaces dentro de un <nav> con nombre.
        Los enlaces dicen a qué página llevan, no "anterior" y "siguiente" a secas, para
        que tengan sentido leídos fuera de contexto.
    --}}
    <nav class="paginacion" aria-label="Paginación de resultados">
        <ul>
            @if ($paginator->onFirstPage())
                <li><span class="desactivado" aria-hidden="true">Anterior</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior<span class="sr-only"> (página {{ $paginator->currentPage() - 1 }})</span></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="desactivado">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span aria-current="page">{{ $page }}<span class="sr-only"> (página actual)</span></span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}<span class="sr-only"> (ir a la página {{ $page }})</span></a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente<span class="sr-only"> (página {{ $paginator->currentPage() + 1 }})</span></a></li>
            @else
                <li><span class="desactivado" aria-hidden="true">Siguiente</span></li>
            @endif
        </ul>
    </nav>
@endif
