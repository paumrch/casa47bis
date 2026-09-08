{{--
    Tarjeta de vivienda.

    El encabezado es un enlace dentro de un h3: el lector de pantalla puede listar los
    resultados navegando por encabezados, y cada enlace dice a dónde va sin depender del
    contexto visual. Nada de tarjetas enteras clicables con un "ver más" repetido veinte
    veces, que es lo que convierte una lista de enlaces en ruido.
--}}
<li class="tarjeta">
    <div class="imagen" role="presentation">
        {{-- Proporción reservada en CSS: la imagen no desplaza el texto al cargar. --}}
        <span aria-hidden="true">{{ $property->bedrooms }} dorm · {{ (int) $property->surface_m2 }} m²</span>
    </div>

    <div class="cuerpo">
        <h3>
            <a href="{{ route('viviendas.show', $property) }}">
                {{ $property->development->name }}
                <span class="sr-only"></span>
            </a>
        </h3>

        <p class="ubicacion">{{ $property->municipality }}, {{ $property->province }}</p>

        <p class="renta">{{ number_format((float) $property->monthly_rent, 0, ',', '.') }} €<small> al mes</small></p>

        <dl>
            <div><dt>Dormitorios:</dt><dd>{{ $property->bedrooms }}</dd></div>
            <div><dt>Baños:</dt><dd>{{ $property->bathrooms }}</dd></div>
            <div><dt>Superficie:</dt><dd>{{ number_format((float) $property->surface_m2, 0, ',', '.') }} m²</dd></div>
        </dl>

        @if($property->accessible)
            <p style="margin:0"><span class="etiqueta">Vivienda accesible</span></p>
        @endif
    </div>
</li>
