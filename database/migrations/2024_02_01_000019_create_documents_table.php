<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Documento aportado o generado: metadatos, hash, referencia en
 * almacenamiento, estado de validación y de firma (§5.2). NUNCA el
 * binario: el fichero vive en almacenamiento externo, aquí sólo
 * `storage_key` (§9, decisión 9).
 *
 * `documentable_type`/`documentable_id` es un morph porque un documento
 * puede adjuntarse a una solicitud, una incidencia o, en el futuro, otra
 * entidad, sin necesidad de una FK por cada caso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('documentable');
            $table->nullableUuidMorphs('uploaded_by');

            $table->string('document_type', 50); // dni, nomina, empadronamiento...
            $table->string('storage_key');
            $table->char('checksum_sha256', 64);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');

            $table->string('scan_status', 20)->default('pending');
            $table->string('signature_status', 20)->default('not_applicable');

            $table->timestamps();

            $table->index('document_type');
        });

        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_scan_status_check CHECK (scan_status IN ('pending', 'clean', 'infected', 'error'))");
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_signature_status_check CHECK (signature_status IN ('not_applicable', 'pending', 'signed', 'invalid'))");
        DB::statement("COMMENT ON TABLE documents IS 'Categoría documental (§5.4): almacenamiento cifrado, acceso sólo por URL firmada de corta vigencia. Retención conforme al expediente al que pertenecen; nunca se guarda el binario en esta base.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
