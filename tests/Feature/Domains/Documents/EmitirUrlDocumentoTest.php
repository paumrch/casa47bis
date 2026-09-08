<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Documents;

use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Documents\Actions\IssueDocumentUrl;
use App\Domains\Documents\DocumentType;
use App\Domains\Documents\Exceptions\DocumentAccessDenied;
use App\Domains\Documents\Exceptions\DocumentNotScanned;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\ScanStatus;
use App\Domains\Documents\SignatureStatus;
use App\Integrations\Storage\FilesystemDocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Emitir la URL firmada de descarga de un documento.
 *
 * Se audita la EMISIÓN, no la descarga -el porqué está en el comentario de
 * `IssueDocumentUrl»-, así que la prueba que demuestra la auditoría comprueba que se
 * escribe un evento al pedir la URL, no que se escriba al abrirla.
 */
final class EmitirUrlDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private IssueDocumentUrl $issue;

    private FilesystemDocumentStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $disk = Storage::fake('local');
        $this->storage = new FilesystemDocumentStorage($disk);

        $this->issue = new IssueDocumentUrl(
            DB::connection(),
            $this->storage,
            new AuditRecorder(DB::connection()),
        );
    }

    private function crearDocumento(ScanStatus $scanStatus, ?string $uploadedById = null): Document
    {
        $storageKey = $this->storage->put('contenido del documento', 'application/pdf');

        return Document::query()->create([
            'documentable_type' => 'application',
            'documentable_id' => (string) Str::uuid7(),
            'uploaded_by_type' => $uploadedById === null ? null : 'account',
            'uploaded_by_id' => $uploadedById,
            'document_type' => DocumentType::NationalId,
            'storage_key' => $storageKey,
            'checksum_sha256' => hash('sha256', 'contenido del documento'),
            'mime_type' => 'application/pdf',
            'size_bytes' => strlen('contenido del documento'),
            'scan_status' => $scanStatus,
            'signature_status' => SignatureStatus::NotApplicable,
        ]);
    }

    #[Test]
    public function un_documento_infectado_no_puede_descargarse(): void
    {
        $documento = $this->crearDocumento(ScanStatus::Infected);

        $this->expectException(DocumentNotScanned::class);

        ($this->issue)($documento, AuditActor::staff((string) Str::uuid7()));
    }

    #[Test]
    public function un_documento_pendiente_de_analisis_no_puede_descargarse(): void
    {
        $documento = $this->crearDocumento(ScanStatus::Pending);

        $this->expectException(DocumentNotScanned::class);

        ($this->issue)($documento, AuditActor::staff((string) Str::uuid7()));
    }

    #[Test]
    public function un_documento_con_analisis_en_error_no_puede_descargarse(): void
    {
        $documento = $this->crearDocumento(ScanStatus::Error);

        $this->expectException(DocumentNotScanned::class);

        ($this->issue)($documento, AuditActor::staff((string) Str::uuid7()));
    }

    #[Test]
    public function un_solicitante_sin_derecho_sobre_el_documento_no_puede_pedir_la_url(): void
    {
        $propietarioId = (string) Str::uuid7();
        $documento = $this->crearDocumento(ScanStatus::Clean, $propietarioId);

        $otroSolicitante = AuditActor::citizen((string) Str::uuid7());

        $this->expectException(DocumentAccessDenied::class);

        ($this->issue)($documento, $otroSolicitante);
    }

    #[Test]
    public function la_url_firmada_caduca_en_minutos_y_se_audita_su_emision(): void
    {
        $documento = $this->crearDocumento(ScanStatus::Clean);
        $actor = AuditActor::staff((string) Str::uuid7(), '192.0.2.20');

        $antesDeEmitir = now();

        $url = ($this->issue)($documento, $actor);

        $this->assertNotSame('', $url);

        // No se afirma la vigencia exacta a partir de la URL -cada proveedor firma de
        // forma distinta-, se afirma lo que el dominio garantiza: se registra un evento
        // de auditoría con una fecha de caducidad en minutos, no en horas, a partir del
        // instante de la emisión.
        $evento = DB::table('audit_events')
            ->where('entity_id', $documento->id)
            ->where('action', 'document.url_issued')
            ->first();

        $this->assertNotNull($evento, 'La emisión de la URL debe quedar registrada en auditoría.');

        $metadata = json_decode((string) $evento->metadata, true, 512, JSON_THROW_ON_ERROR);
        $expiresAt = Carbon::parse($metadata['expires_at']);

        $this->assertTrue(
            $expiresAt->between($antesDeEmitir->copy()->addMinutes(4), $antesDeEmitir->copy()->addMinutes(6)),
            'La URL debe caducar en unos pocos minutos, no en horas.',
        );
    }
}
