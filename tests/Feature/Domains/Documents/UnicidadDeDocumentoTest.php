<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Documents;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El mismo documento no se guarda dos veces en el mismo expediente.
 *
 * La acción de almacenar ya comprueba el hash antes de escribir, y esa comprobación
 * cubre el caso normal: alguien sube el mismo PDF dos veces «por si acaso». Pero entre
 * leer y escribir hay una ventana, y dos peticiones simultáneas -dos pestañas, un doble
 * clic, un reintento del navegador- pueden colarse las dos.
 *
 * Como con la unicidad de adjudicación, la garantía de verdad está en la base de datos:
 * ahí resiste a la concurrencia, a un error de código y a una escritura manual. La
 * comprobación en la aplicación existe para dar un mensaje decente al usuario, no para
 * garantizar nada.
 */
final class UnicidadDeDocumentoTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function fila(string $expedienteId, string $checksum): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'documentable_type' => 'application',
            'documentable_id' => $expedienteId,
            'document_type' => 'income_tax_return',
            'storage_key' => 'documents/2026/09/'.Str::uuid7().'.pdf',
            'checksum_sha256' => $checksum,
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'scan_status' => 'pending',
            'signature_status' => 'not_applicable',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    #[Test]
    public function la_base_rechaza_el_mismo_contenido_dos_veces_en_el_mismo_expediente(): void
    {
        $expediente = (string) Str::uuid7();
        $checksum = hash('sha256', 'contenido del documento');

        DB::table('documents')->insert($this->fila($expediente, $checksum));

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('documents')->insert($this->fila($expediente, $checksum));
    }

    #[Test]
    public function el_mismo_contenido_si_puede_aparecer_en_expedientes_distintos(): void
    {
        $checksum = hash('sha256', 'certificado idéntico');

        DB::table('documents')->insert($this->fila((string) Str::uuid7(), $checksum));
        DB::table('documents')->insert($this->fila((string) Str::uuid7(), $checksum));

        // Dos personas pueden aportar el mismo certificado -un modelo oficial en blanco,
        // un justificante idéntico- y cada expediente debe conservar el suyo. La
        // restricción es por expediente, no global, y esto lo comprueba.
        $this->assertSame(2, DB::table('documents')->where('checksum_sha256', $checksum)->count());
    }

    #[Test]
    public function un_expediente_admite_documentos_distintos(): void
    {
        $expediente = (string) Str::uuid7();

        DB::table('documents')->insert($this->fila($expediente, hash('sha256', 'nómina de enero')));
        DB::table('documents')->insert($this->fila($expediente, hash('sha256', 'nómina de febrero')));

        $this->assertSame(2, DB::table('documents')->where('documentable_id', $expediente)->count());
    }
}
