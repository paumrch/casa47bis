<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Domains\Housing\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaCatalogoDePrueba;
use Tests\TestCase;

/**
 * El portal público: contenido, filtros y accesibilidad estructural.
 *
 * La comprobación de fondo es que **el contenido está en el HTML servido**. Todo lo demás
 * —que se indexe, que lo lea un lector de pantalla sin esperar, que funcione en un móvil
 * viejo con mala red, que aparezca rápido— se sigue de ahí.
 */
final class PortalPublicoTest extends TestCase
{
    use CreaCatalogoDePrueba;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearCatalogoDePrueba();
    }

    /** @return array<string, array{string}> */
    public static function paginasPublicas(): array
    {
        return [
            'inicio' => ['/'],
            'catálogo' => ['/viviendas'],
            'convocatorias' => ['/convocatorias'],
            'requisitos' => ['/requisitos'],
            'accesibilidad' => ['/accesibilidad'],
            'aviso legal' => ['/aviso-legal'],
            'privacidad' => ['/privacidad'],
        ];
    }

    #[Test]
    #[DataProvider('paginasPublicas')]
    public function la_pagina_responde_y_tiene_estructura_accesible(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();
        $this->assertIsString($html);

        $this->assertStringContainsString('<html lang="es"', $html, 'Falta el idioma del documento.');
        $this->assertSame(1, preg_match_all('/<h1\b/i', $html), 'Debe haber exactamente un h1.');
        $this->assertStringContainsString('Saltar al contenido', $html, 'Falta el enlace para saltar al contenido.');
        $this->assertStringContainsString('<main', $html, 'Falta la región principal.');
        $this->assertMatchesRegularExpression('/<title>.+<\/title>/', $html, 'Falta el título del documento.');
        $this->assertDoesNotMatchRegularExpression('/<h[3-6][^>]*>/i', substr($html, 0, (int) strpos($html, '<h1')), 'Hay encabezados profundos antes del h1.');
    }

    #[Test]
    public function el_catalogo_muestra_las_viviendas_en_el_html_servido(): void
    {
        $property = Property::query()->available()->with('development')->firstOrFail();

        $response = $this->get('/viviendas');

        // El nombre de la promoción aparece en el HTML crudo, sin ejecutar nada.
        $response->assertOk()->assertSee($property->development->name, escape: false);
        $response->assertSee('viviendas encontradas', escape: false);
    }

    #[Test]
    public function los_filtros_del_catalogo_filtran_de_verdad(): void
    {
        $conTres = Property::query()->available()->withMinimumBedrooms(3)->count();

        $this->get('/viviendas?dormitorios=3')
            ->assertOk()
            ->assertSee(number_format($conTres, 0, ',', '.').' viviendas encontradas', escape: false);
    }

    #[Test]
    public function el_filtro_de_accesibilidad_solo_devuelve_viviendas_accesibles(): void
    {
        $accesibles = Property::query()->available()->accessible()->count();

        $this->get('/viviendas?accesible=1')
            ->assertOk()
            ->assertSee(number_format($accesibles, 0, ',', '.').' viviendas encontradas', escape: false);
    }

    #[Test]
    public function una_busqueda_sin_resultados_explica_que_hacer(): void
    {
        $this->get('/viviendas?municipio=Municipio+Inexistente')
            ->assertOk()
            ->assertSee('No hemos encontrado viviendas', escape: false)
            ->assertSee('Prueba a ampliar', escape: false);
    }

    #[Test]
    public function los_filtros_sobreviven_a_la_paginacion(): void
    {
        $html = $this->get('/viviendas?dormitorios=2')->assertOk()->getContent();
        $this->assertIsString($html);

        if (! str_contains($html, 'paginacion')) {
            $this->markTestSkipped('No hay suficientes resultados para paginar.');
        }

        $this->assertStringContainsString('dormitorios=2', $html, 'Los enlaces de paginación pierden los filtros.');
    }

    #[Test]
    public function los_parametros_hostiles_no_rompen_el_catalogo(): void
    {
        // Valores que no son números, negativos, absurdamente grandes y una cadena larga.
        $this->get('/viviendas?dormitorios=abc&renta_max=-999&q='.str_repeat('a', 5000))
            ->assertOk();

        $this->get('/viviendas?dormitorios[]=1')->assertOk();
    }

    #[Test]
    public function la_ficha_de_una_vivienda_muestra_sus_caracteristicas(): void
    {
        $property = Property::query()->available()->with('development')->firstOrFail();

        $this->get('/viviendas/'.$property->reference_code)
            ->assertOk()
            ->assertSee($property->reference_code, escape: false)
            ->assertSee($property->municipality, escape: false)
            ->assertSee('Características', escape: false);
    }

    #[Test]
    public function una_vivienda_inexistente_devuelve_404(): void
    {
        $this->get('/viviendas/NO-EXISTE-001')->assertNotFound();
    }

    #[Test]
    public function todos_los_campos_del_formulario_tienen_etiqueta_asociada(): void
    {
        $html = $this->get('/viviendas')->assertOk()->getContent();
        $this->assertIsString($html);

        preg_match_all('/<(?:input|select)\b[^>]*\bid="([^"]+)"/i', $html, $campos);
        preg_match_all('/<label\b[^>]*\bfor="([^"]+)"/i', $html, $etiquetas);

        $sinEtiqueta = array_diff($campos[1], $etiquetas[1]);

        $this->assertSame(
            [],
            array_values($sinEtiqueta),
            'Hay campos de formulario sin etiqueta asociada: '.implode(', ', $sinEtiqueta),
        );
    }

    #[Test]
    public function la_pagina_de_requisitos_se_genera_desde_las_reglas_vigentes(): void
    {
        // Los umbrales que se publican salen del mismo código que evalúa las solicitudes.
        // Si alguien cambiara el IPREM en RuleSet2026, esta prueba lo reflejaría sin
        // tocar ninguna plantilla.
        $this->get('/requisitos')
            ->assertOk()
            ->assertSee('16.800,00 €', escape: false)
            ->assertSee('63.000,00 €', escape: false)
            ->assertSee('2026.1', escape: false);
    }

    #[Test]
    public function la_zona_publica_no_instala_cookies(): void
    {
        $response = $this->get('/viviendas')->assertOk();

        $this->assertSame(
            [],
            $response->headers->getCookies(),
            'La zona pública no debe instalar cookies: no hay nada que consentir y no '.
            'hace falta banner.',
        );
    }
}
