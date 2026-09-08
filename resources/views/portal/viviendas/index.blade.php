@extends('layouts.portal')

@section('titulo', 'Viviendas disponibles')
@section('descripcion', 'Busca entre las viviendas de alquiler asequible disponibles por municipio, número de dormitorios, renta máxima y accesibilidad.')

@section('contenido')
    <h1>Viviendas disponibles</h1>

    {{--
        Los filtros son un formulario GET. Sin JavaScript.

        Consecuencias, todas deseables: la búsqueda se puede guardar en favoritos y
        compartir por WhatsApp, el botón de volver del navegador funciona, la página se
        puede recargar sin reenviar nada, y funciona igual en un móvil antiguo con la red
        justa — que es el dispositivo de buena parte de quien busca vivienda asequible.
    --}}
    <form class="filtros" method="get" action="{{ route('viviendas.index') }}" role="search" aria-labelledby="titulo-filtros">
        <h2 id="titulo-filtros" style="margin-top:0">Filtrar la búsqueda</h2>

        <div class="campos">
            <div class="campo">
                <label for="q">Buscar
                    <span class="ayuda">Municipio o referencia</span>
                </label>
                <input type="text" id="q" name="q" value="{{ $filters->search }}" autocomplete="off">
            </div>

            <div class="campo">
                <label for="municipio">Municipio</label>
                <select id="municipio" name="municipio">
                    <option value="">Todos</option>
                    @foreach($municipalities as $municipality)
                        <option value="{{ $municipality }}" @selected($filters->municipality === $municipality)>{{ $municipality }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="dormitorios">Dormitorios
                    <span class="ayuda">Mínimo</span>
                </label>
                <select id="dormitorios" name="dormitorios">
                    <option value="">Cualquiera</option>
                    @foreach([1, 2, 3, 4] as $n)
                        <option value="{{ $n }}" @selected($filters->bedrooms === $n)>{{ $n }} o más</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="renta_max">Renta máxima
                    <span class="ayuda">Euros al mes</span>
                </label>
                <input type="number" id="renta_max" name="renta_max" min="0" max="5000" step="50"
                       value="{{ $filters->maxRent }}" inputmode="numeric">
            </div>

            <div class="casilla">
                <input type="checkbox" id="accesible" name="accesible" value="1" @checked($filters->accessibleOnly)>
                <label for="accesible">Sólo viviendas accesibles</label>
            </div>
        </div>

        <div class="acciones">
            <button type="submit" class="boton">Buscar</button>

            @if($filters->any())
                <a class="boton secundario" href="{{ route('viviendas.index') }}">
                    Quitar los {{ $filters->activeCount() }} filtros
                </a>
            @endif
        </div>
    </form>

    {{--
        El recuento va en una región activa y con role="status": cuando alguien filtra,
        un lector de pantalla anuncia cuántos resultados hay sin que la persona tenga que
        salir a buscarlo.
    --}}
    <p class="resumen-resultados" role="status">
        @if($properties->total() === 0)
            Ninguna vivienda coincide con la búsqueda.
        @elseif($properties->total() === 1)
            1 vivienda encontrada.
        @else
            {{ number_format($properties->total(), 0, ',', '.') }} viviendas encontradas.
        @endif
    </p>

    @if($properties->isEmpty())
        <div class="vacio">
            <h2>No hemos encontrado viviendas con esos criterios</h2>
            <p>
                Prueba a ampliar la renta máxima, a reducir el número de dormitorios o a
                buscar en otro municipio.
            </p>
            @if($filters->any())
                <a class="boton" href="{{ route('viviendas.index') }}">Ver todas las viviendas</a>
            @endif
        </div>
    @else
        <ul class="tarjetas">
            @each('partials.tarjeta-vivienda', $properties, 'property')
        </ul>

        {{ $properties->links('vendor.pagination.portal') }}
    @endif
@endsection
