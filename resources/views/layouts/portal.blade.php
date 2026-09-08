<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · Vivienda de alquiler asequible</title>
    <meta name="description" content="@yield('descripcion', 'Consulta las viviendas de alquiler asequible disponibles, las convocatorias abiertas y los requisitos de acceso.')">

    {{--
        Una sola hoja de estilos, escrita a mano, servida desde el propio dominio.
        Ni fuentes externas, ni CDN de terceros, ni precarga de nada: cada origen ajeno
        es una petición más, una dependencia más y un tratamiento de datos más que
        justificar ante el RGPD.

        El parámetro de versión es la fecha de modificación del fichero: invalida la
        caché al desplegar sin necesidad de una cadena de compilación.
    --}}
    <link rel="stylesheet" href="{{ asset('assets/portal.css') }}?v={{ $cssVersion }}">
    <link rel="icon" href="data:,">
</head>
<body>
    <a class="saltar" href="#contenido">Saltar al contenido principal</a>

    <header class="principal">
        <div class="contenedor">
            <a class="marca" href="{{ route('inicio') }}">Alquiler <span>asequible</span></a>

            <nav class="principal" aria-label="Navegación principal">
                <ul>
                    <li><a href="{{ route('viviendas.index') }}" @if(request()->routeIs('viviendas.*')) aria-current="page" @endif>Viviendas</a></li>
                    <li><a href="{{ route('convocatorias.index') }}" @if(request()->routeIs('convocatorias.*')) aria-current="page" @endif>Convocatorias</a></li>
                    <li><a href="{{ route('requisitos') }}" @if(request()->routeIs('requisitos')) aria-current="page" @endif>Requisitos</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenido" class="contenedor">
        @yield('contenido')
    </main>

    <footer class="principal">
        <div class="contenedor">
            <nav aria-label="Enlaces legales">
                <ul>
                    <li><a href="{{ route('accesibilidad') }}">Accesibilidad</a></li>
                    <li><a href="{{ route('aviso-legal') }}">Aviso legal</a></li>
                    <li><a href="{{ route('privacidad') }}">Protección de datos</a></li>
                </ul>
            </nav>
            <p style="margin-top:1rem;color:var(--texto-suave)">
                Demostrador técnico. Los datos mostrados son ficticios y se generan para
                pruebas; no corresponden a viviendas reales.
            </p>
        </div>
    </footer>
</body>
</html>
