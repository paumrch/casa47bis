@extends('layouts.portal')

@section('titulo', 'Convocatorias')
@section('descripcion', 'Convocatorias de adjudicación de vivienda de alquiler asequible: plazos abiertos y convocatorias resueltas.')

@section('contenido')
    <h1>Convocatorias</h1>

    <p class="entradilla">
        Las viviendas se adjudican por convocatoria. Sólo se pueden presentar solicitudes
        mientras el plazo está abierto.
    </p>

    <section aria-labelledby="abiertas">
        <h2 id="abiertas">Abiertas</h2>

        @if($open->isEmpty())
            <div class="vacio">
                <h3 style="margin-top:0">No hay ninguna convocatoria abierta</h3>
                <p>
                    Cuando se abra la siguiente se publicará aquí, con su plazo y las viviendas
                    incluidas. Mientras tanto puedes revisar los
                    <a href="{{ route('requisitos') }}">requisitos de acceso</a>.
                </p>
            </div>
        @else
            <ul class="tarjetas">
                @foreach($open as $call)
                    <li class="tarjeta">
                        <div class="cuerpo">
                            <h3><a href="{{ route('convocatorias.show', $call) }}">{{ $call->name }}</a></h3>
                            <p class="ubicacion">Referencia {{ $call->reference_code }}</p>
                            <p style="margin:0">
                                Hasta el
                                <time datetime="{{ $call->closes_at->toDateString() }}">{{ $call->closes_at->translatedFormat('j \d\e F \d\e Y') }}</time>
                            </p>
                            @php($dias = $call->daysRemaining())
                            @if($dias !== null)
                                <p style="margin:0"><span class="etiqueta">{{ $dias === 0 ? 'Último día' : "Quedan {$dias} días" }}</span></p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if($others->isNotEmpty())
        <section aria-labelledby="anteriores">
            <h2 id="anteriores">Anteriores</h2>

            <ul class="tarjetas">
                @foreach($others as $call)
                    <li class="tarjeta">
                        <div class="cuerpo">
                            <h3><a href="{{ route('convocatorias.show', $call) }}">{{ $call->name }}</a></h3>
                            <p class="ubicacion">
                                Cerrada el
                                <time datetime="{{ $call->closes_at->toDateString() }}">{{ $call->closes_at->translatedFormat('j \d\e F \d\e Y') }}</time>
                            </p>
                            <p style="margin:0"><span class="etiqueta cerrada">Plazo cerrado</span></p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
