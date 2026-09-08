<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Documents;

use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Documents\Actions\StoreDocument;
use App\Domains\Documents\DocumentType;
use App\Domains\Documents\Exceptions\DocumentTooLarge;
use App\Domains\Documents\Exceptions\UnsupportedDocumentType;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\ScanStatus;
use App\Domains\Documents\SignatureStatus;
use App\Integrations\Storage\FilesystemDocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Almacenar un documento acreditativo.
 *
 * La prueba que más importa aquí no es la del camino feliz, es
 * `un_ejecutable_disfrazado_de_pdf_se_rechaza`: demuestra que la validación confía en
 * los bytes reales del fichero y no en lo que dice su nombre o su cabecera, que es
 * exactamente lo que controla quien intenta subir algo indebido.
 */
final class AlmacenarDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private StoreDocument $store;

    protected function setUp(): void
    {
        parent::setUp();

        $disk = Storage::fake('local');

        $this->store = new StoreDocument(
            DB::connection(),
            new FilesystemDocumentStorage($disk),
            new AuditRecorder(DB::connection()),
        );
    }

    private function actor(): AuditActor
    {
        return AuditActor::citizen((string) Str::uuid7(), '192.0.2.10');
    }

    /** Fichero de prueba con contenido y nombre arbitrarios, subido en modo test. */
    private function ficheroSubido(string $nombre, string $contenido): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($ruta, $contenido);

        return new UploadedFile($ruta, $nombre, null, null, true);
    }

    #[Test]
    public function un_pdf_valido_se_almacena_se_hashea_y_queda_pendiente_de_analisis(): void
    {
        $contenido = "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
        $fichero = $this->ficheroSubido('nomina.pdf', $contenido);

        $documentableId = (string) Str::uuid7();

        $documento = ($this->store)(
            $fichero,
            DocumentType::Payslip,
            'application',
            $documentableId,
            $this->actor(),
        );

        $this->assertInstanceOf(Document::class, $documento);
        $this->assertSame('application', $documento->documentable_type);
        $this->assertSame($documentableId, $documento->documentable_id);
        $this->assertSame(DocumentType::Payslip, $documento->document_type);
        $this->assertSame('application/pdf', $documento->mime_type);
        $this->assertSame(hash('sha256', $contenido), $documento->checksum_sha256);
        $this->assertSame(strlen($contenido), $documento->size_bytes);
        $this->assertSame(ScanStatus::Pending, $documento->scan_status);
        $this->assertSame(SignatureStatus::NotApplicable, $documento->signature_status);
        $this->assertNotSame('', $documento->storage_key);

        $this->assertDatabaseHas('documents', [
            'id' => $documento->id,
            'checksum_sha256' => hash('sha256', $contenido),
            'scan_status' => 'pending',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'entity_id' => $documento->id,
            'action' => 'document.stored',
        ]);
    }

    #[Test]
    public function un_ejecutable_disfrazado_de_pdf_se_rechaza(): void
    {
        // Cabecera ELF (Linux) con extensión .pdf y sin declarar mimetype: el
        // Content-Type que enviaría un navegador no entra en juego, sólo el contenido.
        $contenidoEjecutable = "\x7FELF".str_repeat("\x00", 60);
        $fichero = $this->ficheroSubido('nomina.pdf', $contenidoEjecutable);

        $this->expectException(UnsupportedDocumentType::class);

        ($this->store)(
            $fichero,
            DocumentType::Payslip,
            'application',
            (string) Str::uuid7(),
            $this->actor(),
        );

        $this->assertDatabaseCount('documents', 0);
    }

    #[Test]
    public function un_fichero_demasiado_grande_se_rechaza(): void
    {
        // 11 MiB: por encima del máximo de 10 MiB de StoreDocument.
        $contenidoGrande = "%PDF-1.7\n".str_repeat('A', 11 * 1024 * 1024);
        $fichero = $this->ficheroSubido('nomina.pdf', $contenidoGrande);

        $this->expectException(DocumentTooLarge::class);

        ($this->store)(
            $fichero,
            DocumentType::Payslip,
            'application',
            (string) Str::uuid7(),
            $this->actor(),
        );

        $this->assertDatabaseCount('documents', 0);
    }

    #[Test]
    public function subir_dos_veces_el_mismo_contenido_no_crea_dos_documentos(): void
    {
        $contenido = "%PDF-1.7\ncontenido idéntico\n%%EOF";
        $documentableId = (string) Str::uuid7();

        $primero = ($this->store)(
            $this->ficheroSubido('nomina.pdf', $contenido),
            DocumentType::Payslip,
            'application',
            $documentableId,
            $this->actor(),
        );

        $segundo = ($this->store)(
            // Incluso con otro nombre de fichero: lo que decide la duplicidad es el
            // contenido, nunca el nombre.
            $this->ficheroSubido('nomina-otra-vez.pdf', $contenido),
            DocumentType::Payslip,
            'application',
            $documentableId,
            $this->actor(),
        );

        $this->assertSame($primero->id, $segundo->id);
        $this->assertDatabaseCount('documents', 1);
    }

    #[Test]
    public function el_mismo_contenido_en_expedientes_distintos_si_se_almacena_dos_veces(): void
    {
        $contenido = "%PDF-1.7\ncontenido compartido\n%%EOF";

        $primero = ($this->store)(
            $this->ficheroSubido('dni.pdf', $contenido),
            DocumentType::NationalId,
            'application',
            (string) Str::uuid7(),
            $this->actor(),
        );

        $segundo = ($this->store)(
            $this->ficheroSubido('dni.pdf', $contenido),
            DocumentType::NationalId,
            'application',
            (string) Str::uuid7(),
            $this->actor(),
        );

        $this->assertNotSame($primero->id, $segundo->id);
        $this->assertDatabaseCount('documents', 2);
    }
}
