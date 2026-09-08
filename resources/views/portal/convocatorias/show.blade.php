@extends('layouts.portal')

@section('titulo', $call->name)
@section('descripcion', "Convocatoria {$call->reference_code} de adjudicación de vivienda de alquiler asequible.")

@section('contenido')
    <nav aria-label="Miga de pan" style="font-size:.92rem">
        <a href="{{ route('convocatorias.index') }}">Convocatorias</a> ›
        <span aria-current="page">{{ $call->reference_code }}</span>
    </nav>

    <h1>{{ $call->name }}</h1>

    @if($call->isOpen())
        <p><span class="etiqueta">Plazo abierto</span></p>
    @else
        <p><span class="etiqueta cerrada">Plazo cerrado</span></p>
    @endif

    <h2>Plazos</h2>
    <table class="datos">
        <caption class="sr-only">Fechas de la convocatoria</caption>
        <tbody>
            <tr>
                <th scope="row">Apertura del plazo</th>
                <td><time datetime="{{ $call->opens_at->toDateString() }}">{{ $call->opens_at->translatedFormat('j \d\e F \d\e Y') }}</time></td>
            </tr>
            <tr>
                <th scope="row">Cierre del plazo</th>
                <td><time datetime="{{ $call->closes_at->toDateString() }}">{{ $call->closes_at->translatedFormat('j \d\e F \d\e Y') }}</time></td>
            </tr>
            <tr>
                <th scope="row">Viviendas incluidas</th>
                <td>{{ number_format($propertyCount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th scope="row">Criterios de acceso aplicables</th>
                <td>Versión {{ $call->eligibility_rule_version }}</td>
            </tr>
        </tbody>
    </table>

    {{--
        Se publica la versión de las reglas aplicables a ESTA convocatoria. No es un
        detalle técnico expuesto por descuido: es lo que permite que, dos años después,
        cualquiera pueda comprobar con qué criterios se resolvió. La alternativa es que
        el ciudadano se fíe.
    --}}
    <h2>Requisitos</h2>
    <p>
        Se aplican los <a href="{{ route('requisitos') }}">requisitos de acceso</a> en su
        versión {{ $call->eligibility_rule_version }}, vigente al abrirse esta convocatoria.
    </p>

    @if($call->isOpen())
        <p><a class="boton" href="{{ route('viviendas.index') }}">Ver las viviendas disponibles</a></p>
    @endif
@endsection
