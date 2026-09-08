@extends('layouts.portal')

@section('titulo', 'Declaración de accesibilidad')
@section('descripcion', 'Declaración de accesibilidad del portal conforme al Real Decreto 1112/2018 y la norma EN 301 549.')

@section('contenido')
    <h1>Declaración de accesibilidad</h1>

    <p class="entradilla">
        Este portal es un demostrador técnico. La declaración se redacta con la estructura
        que exige el Real Decreto 1112/2018 para que pueda contrastarse con la de un
        servicio real.
    </p>

    <h2>Situación de cumplimiento</h2>
    <p>
        El portal se ha construido con el objetivo de cumplir el nivel <strong>AA de las
        WCAG 2.1</strong>, conforme a la norma armonizada <strong>EN 301 549</strong>.
    </p>

    <h2>Cómo se ha comprobado</h2>
    <ul>
        <li>Comprobación automática de cada página en la integración continua.</li>
        <li>Verificación de que todo el contenido está en el HTML servido, sin depender de JavaScript.</li>
        <li>Comprobación de la estructura de encabezados y de que cada campo tiene su etiqueta asociada.</li>
    </ul>

    <h2>Lo que todavía no se ha comprobado</h2>
    <p>
        Se declara de forma explícita porque una declaración de accesibilidad que no
        distinga lo verificado de lo supuesto no sirve para nada:
    </p>
    <ul>
        <li>Revisión manual completa con lectores de pantalla reales.</li>
        <li>Auditoría externa por una entidad independiente.</li>
        <li>Pruebas con personas usuarias con discapacidad.</li>
    </ul>
    <p>
        Las herramientas automáticas cubren aproximadamente un tercio de los criterios de
        las WCAG. El resto exige revisión humana, y hasta que se haga no puede afirmarse
        la conformidad.
    </p>

    <h2>Decisiones tomadas por accesibilidad</h2>
    <ul>
        <li>Todo el contenido se entrega en HTML renderizado en servidor: se lee sin esperar a que se ejecute nada.</li>
        <li>Los filtros del catálogo son un formulario estándar, navegable con teclado.</li>
        <li>No hay mapas interactivos incrustados; la ubicación se ofrece como texto y como enlace.</li>
        <li>No se cargan fuentes externas: el texto aparece de inmediato y respeta los ajustes del sistema.</li>
        <li>Existe un enlace para saltar al contenido y un único estilo de foco visible.</li>
    </ul>

    <h2>Vías de reclamación</h2>
    <p>
        En un servicio real, aquí figuraría el procedimiento de queja y reclamación ante el
        organismo responsable, conforme al artículo 12 del Real Decreto 1112/2018.
    </p>
@endsection
