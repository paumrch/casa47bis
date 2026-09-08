@extends('layouts.portal')

@section('titulo', 'Inicio')
@section('descripcion', 'Viviendas de alquiler asequible: consulta la oferta disponible, las convocatorias abiertas y los requisitos de acceso.')

@section('contenido')
    <h1>Vivienda de alquiler asequible</h1>

    <p class="entradilla">
        Consulta las viviendas disponibles, comprueba si cumples los requisitos y presenta
        tu solicitud durante el plazo de una convocatoria abierta.
    </p>

    @if($openCalls->isNotEmpty())
        <section aria-labelledby="convocatorias-abiertas">
            <h2 id="convocatorias-abiertas">Convocatorias abiertas</h2>

            <ul class="tarjetas">
                @foreach($openCalls as $call)
                    <li class="tarjeta">
                        <div class="cuerpo">
                            <h3><a href="{{ route('convocatorias.show', $call) }}">{{ $call->name }}</a></h3>
                            <p class="ubicacion">
                                Plazo de presentación hasta el
                                <time datetime="{{ $call->closes_at->toDateString() }}">{{ $call->closes_at->translatedFormat('j \d\e F \d\e Y') }}</time>.
                            </p>
                            @php($dias = $call->daysRemaining())
                            @if($dias !== null)
                                <p style="margin:0"><span class="etiqueta">{{ $dias === 0 ? 'Último día' : "Quedan {$dias} días" }}</span></p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @else
        <div class="vacio">
            <h2>No hay convocatorias abiertas en este momento</h2>
            <p>
                Puedes consultar igualmente la oferta de viviendas y los requisitos de acceso
                para tenerlo todo preparado cuando se abra la siguiente.
            </p>
            <a class="boton" href="{{ route('requisitos') }}">Consultar los requisitos</a>
        </div>
    @endif

    <section aria-labelledby="oferta">
        <h2 id="oferta">{{ $availableCount }} viviendas disponibles</h2>

        @if($featured->isNotEmpty())
            <ul class="tarjetas">
                @each('partials.tarjeta-vivienda', $featured, 'property')
            </ul>

            <p style="margin-top:1.5rem">
                <a class="boton secundario" href="{{ route('viviendas.index') }}">Ver todas las viviendas</a>
            </p>
        @endif
    </section>
@endsection
