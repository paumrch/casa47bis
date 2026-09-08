@extends('layouts.portal')

@section('titulo', $property->development->name . ' · ' . $property->municipality)
@section('descripcion', "Vivienda de {$property->bedrooms} dormitorios en {$property->municipality}, {$property->province}.")

@section('contenido')
    <nav aria-label="Miga de pan" style="font-size:.92rem">
        <a href="{{ route('viviendas.index') }}">Viviendas</a> ›
        <span aria-current="page">{{ $property->reference_code }}</span>
    </nav>

    <h1>{{ $property->development->name }}</h1>
    <p class="entradilla">{{ $property->municipality }}, {{ $property->province }}</p>

    <div class="ficha">
        <div>
            <h2>Características</h2>

            <table class="datos">
                <caption class="sr-only">Características de la vivienda</caption>
                <tbody>
                    <tr><th scope="row">Referencia</th><td>{{ $property->reference_code }}</td></tr>
                    <tr><th scope="row">Dormitorios</th><td>{{ $property->bedrooms }}</td></tr>
                    <tr><th scope="row">Baños</th><td>{{ $property->bathrooms }}</td></tr>
                    <tr><th scope="row">Superficie</th><td>{{ number_format((float) $property->surface_m2, 2, ',', '.') }} m²</td></tr>
                    @if($property->floor)
                        <tr><th scope="row">Planta</th><td>{{ $property->floor }}</td></tr>
                    @endif
                    <tr><th scope="row">Ascensor</th><td>{{ $property->has_elevator ? 'Sí' : 'No' }}</td></tr>
                    <tr><th scope="row">Vivienda accesible</th><td>{{ $property->accessible ? 'Sí' : 'No' }}</td></tr>
                    <tr><th scope="row">Plaza de garaje</th><td>{{ $property->has_garage ? 'Sí' : 'No' }}</td></tr>
                    <tr><th scope="row">Trastero</th><td>{{ $property->has_storage_room ? 'Sí' : 'No' }}</td></tr>
                </tbody>
            </table>

            <h2>Ubicación</h2>
            <address style="font-style:normal">
                @if($property->address_line){{ $property->address_line }}<br>@endif
                {{ $property->postal_code }} {{ $property->municipality }}<br>
                {{ $property->province }}
            </address>

            {{--
                No hay mapa incrustado.

                Un mapa interactivo son cientos de kilobytes de JavaScript y una
                dependencia de un servicio de teselas, para una información que aquí cabe
                en tres líneas de texto. Quien quiera verlo sobre plano tiene el enlace a
                un servicio de mapas, que además funciona con lector de pantalla y no
                filtra la navegación de nadie a un tercero sin avisar.
            --}}
            @if($property->latitude && $property->longitude)
                <p>
                    <a href="https://www.openstreetmap.org/?mlat={{ $property->latitude }}&amp;mlon={{ $property->longitude }}#map=17/{{ $property->latitude }}/{{ $property->longitude }}"
                       rel="noopener noreferrer">
                        Ver la ubicación en OpenStreetMap
                        <span class="sr-only">(se abre en otro sitio web)</span>
                    </a>
                </p>
            @endif
        </div>

        <aside class="panel" aria-labelledby="condiciones">
            <h2 id="condiciones" style="margin-top:0">Condiciones</h2>

            <p class="renta">{{ number_format((float) $property->monthly_rent, 0, ',', '.') }} €</p>
            <p style="margin-top:0;color:var(--texto-suave)">al mes</p>

            <p>
                Para optar a esta vivienda debes cumplir los requisitos de acceso y presentar
                una solicitud durante el plazo de una convocatoria abierta.
            </p>

            <p><a class="boton" href="{{ route('requisitos') }}">Ver los requisitos</a></p>
            <p style="margin-bottom:0"><a href="{{ route('convocatorias.index') }}">Consultar convocatorias</a></p>
        </aside>
    </div>
@endsection
