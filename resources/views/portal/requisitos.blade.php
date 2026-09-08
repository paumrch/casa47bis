@extends('layouts.portal')

@section('titulo', 'Requisitos de acceso')
@section('descripcion', 'Requisitos para acceder a una vivienda de alquiler asequible: ingresos, residencia, titularidad de vivienda y obligaciones tributarias.')

@section('contenido')
    <h1>Requisitos de acceso</h1>

    <p class="entradilla">
        Para optar a una vivienda debes cumplir todos los requisitos siguientes en el
        momento de presentar la solicitud.
    </p>

    {{--
        Esta lista NO está escrita aquí: se genera desde el conjunto de reglas vigente,
        el mismo que evalúa las solicitudes. Es imposible que la página publique un
        umbral y el sistema aplique otro.
    --}}
    @foreach($requirements as $requirement)
        <section aria-labelledby="req-{{ $requirement['code'] }}">
            <h2 id="req-{{ $requirement['code'] }}">{{ $requirement['title'] }}</h2>
            <p>{{ $requirement['detail'] }}</p>
        </section>
    @endforeach

    <h2>Cómo se comprueban</h2>
    <p>
        Siempre que sea posible, los datos se consultan directamente a las administraciones
        que los custodian, para no pedirte documentos que la Administración ya tiene.
        Cuando esa consulta no esté disponible, se te solicitará la documentación
        acreditativa correspondiente.
    </p>

    <h2>Versión de estos requisitos</h2>
    <p>
        Versión <strong>{{ $version }}</strong>, aplicable desde el
        <time datetime="{{ $effectiveFrom->format('Y-m-d') }}">{{ $effectiveFrom->format('d/m/Y') }}</time>.
    </p>
    <p>
        Cada solicitud se resuelve con la versión vigente en el momento de presentarla,
        aunque los requisitos cambien después. Las versiones anteriores se conservan para
        poder justificar cualquier resolución.
    </p>
@endsection
