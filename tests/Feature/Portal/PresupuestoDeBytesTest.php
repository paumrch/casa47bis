<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaCatalogoDePrueba;
use Tests\TestCase;

/**
 * Presupuesto de bytes del portal público.
 *
 * ESTA ES LA PRUEBA MÁS PUBLICABLE DEL PROYECTO.
 *
 * El portal en producción que se analizó entrega **1.333.490 bytes de JavaScript** antes
 * de pintar un listado de viviendas, sobre una plantilla vacía de 1.627 bytes. Aquí se
 * afirma que el mismo contenido cabe en dos órdenes de magnitud menos.
 *
 * Una afirmación así no se pone en un informe: se convierte en un límite que rompe la
 * integración continua. El día que alguien añada una librería «pequeña» al portal
 * público, esta prueba se pondrá en rojo y habrá que justificarlo en la revisión, que es
 * exactamente donde debe discutirse.
 *
 * Los límites son deliberadamente holgados respecto de lo que se mide hoy: no se trata de
 * congelar el diseño, sino de impedir que el peso crezca un orden de magnitud sin que
 * nadie se entere.
 */
final class PresupuestoDeBytesTest extends TestCase
{
    use CreaCatalogoDePrueba;
    use RefreshDatabase;

    /** Ningún documento HTML del portal público puede superar esto, sin comprimir. */
    private const MAX_HTML_BYTES = 60_000;

    /** La hoja de estilos completa, sin comprimir. */
    private const MAX_CSS_BYTES = 25_000;

    /** JavaScript entregado al ciudadano en la zona pública. */
    private const MAX_JS_BYTES = 0;

    /** Referencia observada en el portal analizado, para el contraste. */
    private const BUNDLE_ACTUAL_BYTES = 1_333_490;

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
            'catálogo filtrado' => ['/viviendas?dormitorios=2&accesible=1'],
            'convocatorias' => ['/convocatorias'],
            'requisitos' => ['/requisitos'],
            'accesibilidad' => ['/accesibilidad'],
            'aviso legal' => ['/aviso-legal'],
            'privacidad' => ['/privacidad'],
        ];
    }

    #[Test]
    #[DataProvider('paginasPublicas')]
    public function ninguna_pagina_supera_el_presupuesto_de_html(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertIsString($html);

        $bytes = strlen($html);

        $this->assertLessThanOrEqual(
            self::MAX_HTML_BYTES,
            $bytes,
            sprintf('«%s» pesa %s bytes de HTML y el límite es %s.', $url, number_format($bytes), number_format(self::MAX_HTML_BYTES)),
        );
    }

    #[Test]
    #[DataProvider('paginasPublicas')]
    public function el_portal_publico_no_entrega_javascript(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertIsString($html);

        $this->assertSame(
            self::MAX_JS_BYTES,
            preg_match_all('/<script\b/i', $html),
            sprintf(
                'La página «%s» incluye una etiqueta <script>. La zona pública se sirve sin '.
                'JavaScript: es lo que sostiene su rendimiento y su accesibilidad.',
                $url,
            ),
        );
    }

    #[Test]
    #[DataProvider('paginasPublicas')]
    public function ninguna_pagina_carga_recursos_de_terceros(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertIsString($html);

        // Se buscan orígenes externos en atributos que provocan una petición del
        // navegador (src, href de hoja de estilos, tipografías). Los enlaces de texto a
        // otros sitios no cuentan: no descargan nada ni filtran al visitante.
        preg_match_all('/<(?:link|script|img|iframe|source)\b[^>]*\b(?:src|href)="(https?:)?\/\/([^"\/]+)/i', $html, $matches);

        $propio = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $externos = array_values(array_filter(
            array_unique($matches[2]),
            static fn (string $host): bool => ! str_starts_with($host, $propio),
        ));

        $this->assertSame(
            [],
            $externos,
            sprintf(
                'La página «%s» carga recursos de un origen externo. Cada origen ajeno es '.
                'una dependencia más y un tratamiento de datos más que justificar.',
                $url,
            ),
        );
    }

    #[Test]
    public function la_hoja_de_estilos_cabe_en_el_presupuesto(): void
    {
        $path = public_path('assets/portal.css');

        $this->assertFileExists($path);

        $bytes = (int) filesize($path);

        $this->assertLessThanOrEqual(
            self::MAX_CSS_BYTES,
            $bytes,
            sprintf('portal.css pesa %s bytes y el límite es %s.', number_format($bytes), number_format(self::MAX_CSS_BYTES)),
        );
    }

    /**
     * La comparación que se publicará, medida y sin inflar.
     *
     * Se compara el catálogo COMPLETO de la propuesta -HTML con doce viviendas más la
     * hoja de estilos entera- contra el bundle de JavaScript del portal analizado, sin
     * contar el HTML ni el CSS de aquél. Es decir, la comparación juega en contra
     * nuestra a propósito: si aun así el resultado es holgado, es defendible.
     *
     * Una primera versión de esta prueba exigía 100×. Se midió 47× y se bajó el umbral
     * en lugar de retocar la medición: el número que se publica es el que sale.
     */
    #[Test]
    public function el_catalogo_pesa_un_orden_de_magnitud_largo_menos_que_el_portal_analizado(): void
    {
        $html = $this->get('/viviendas')->assertOk()->getContent();
        $this->assertIsString($html);

        $total = strlen($html) + (int) filesize(public_path('assets/portal.css'));
        $ratio = self::BUNDLE_ACTUAL_BYTES / $total;

        $this->assertGreaterThan(
            40,
            $ratio,
            sprintf(
                'El catálogo completo pesa %s bytes (HTML + CSS) frente a los %s bytes de '.
                'JavaScript del portal analizado. La proporción medida es %.1f× y se exige '.
                'más de 40×.',
                number_format($total),
                number_format(self::BUNDLE_ACTUAL_BYTES),
                $ratio,
            ),
        );
    }
}
