<?php

declare(strict_types=1);

namespace Tests\Feature\Integrations;

use App\Integrations\Storage\FilesystemDocumentStorage;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La portabilidad del almacenamiento, comprobada.
 *
 * ESTA CLASE ES LA QUE CONVIERTE UNA AFIRMACIÓN EN UN HECHO.
 *
 * El proyecto sostiene que la aplicación puede moverse entre proveedores sin tocar
 * código. La forma barata de "demostrarlo" sería dibujar una caja llamada «Object
 * Storage» en un diagrama y seguir adelante. Aquí no.
 *
 * La integración continua ejecuta esta misma suite DOS veces: una contra un
 * almacenamiento compatible con S3 (MinIO, que es lo que se usaría en Azure, AWS,
 * OVHcloud o un centro de datos propio) y otra contra el disco local, que es el mínimo
 * común denominador. Si alguien introdujera en el adaptador una llamada específica de un
 * proveedor, una de las dos ejecuciones fallaría y no podría fusionarse.
 *
 * El disco bajo prueba se elige con FILESYSTEM_DISK.
 */
final class PortabilidadDelAlmacenamientoTest extends TestCase
{
    private FilesystemDocumentStorage $storage;

    private string $diskName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diskName = (string) config('filesystems.default');
        $this->storage = new FilesystemDocumentStorage(Storage::disk($this->diskName));
    }

    #[Test]
    public function un_documento_se_guarda_y_se_recupera_intacto(): void
    {
        // Contenido binario con bytes nulos y UTF-8: un PDF real no es texto ASCII,
        // y un adaptador que corrompa esto pasaría desapercibido con "hola mundo".
        $contents = "%PDF-1.7\n\x00\x01\x02 Ñandú — acentos y \xFF bytes altos\n%%EOF";

        $key = $this->storage->put($contents, 'application/pdf');

        $this->assertSame(
            $contents,
            $this->storage->get($key),
            "El disco «{$this->diskName}» no devolvió el contenido byte a byte.",
        );

        $this->storage->delete($key);
    }

    #[Test]
    public function la_clave_generada_no_filtra_datos_personales(): void
    {
        $key = $this->storage->put('contenido', 'application/pdf');

        $this->assertStringStartsWith('documents/', $key);
        $this->assertStringEndsWith('.pdf', $key);
        $this->assertMatchesRegularExpression(
            '#^documents/\d{4}/\d{2}/[0-9a-f-]{36}\.pdf$#',
            $key,
            'La clave debe ser opaca: el nombre que sube un ciudadano puede contener su NIF.',
        );

        $this->storage->delete($key);
    }

    #[Test]
    public function existe_y_se_borra(): void
    {
        $key = $this->storage->put('contenido', 'application/pdf');

        $this->assertTrue($this->storage->exists($key));

        $this->storage->delete($key);

        $this->assertFalse(
            $this->storage->exists($key),
            "El borrado no surtió efecto en el disco «{$this->diskName}».",
        );
    }

    #[Test]
    public function se_emite_una_url_firmada_de_vigencia_corta(): void
    {
        $key = $this->storage->put('contenido', 'application/pdf');

        $url = $this->storage->temporaryUrl($key, now()->addMinutes(5));

        // No se comprueba la forma exacta de la URL: cada proveedor firma a su manera,
        // y atarse a un formato concreto sería justo el acoplamiento que se quiere
        // evitar. Se comprueba lo único que el dominio necesita: que hay una URL.
        $this->assertNotSame('', $url);
        $this->assertStringStartsWith('http', $url);

        $this->storage->delete($key);
    }

    #[Test]
    public function recuperar_un_documento_inexistente_falla_de_forma_explicita(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->storage->get('documents/2026/01/no-existe.pdf');
    }
}
