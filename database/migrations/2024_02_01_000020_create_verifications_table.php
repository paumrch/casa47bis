<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resultado de una comprobación concreta (ingresos, titularidad, estar al
 * corriente de pago...). Sustituye a `EligibilityCheck` como entidad
 * propia: sirve tanto para elegibilidad como para baremación y
 * comprobaciones posteriores (§5.3).
 *
 * `verifiable_type`/`verifiable_id` es un morph: una verificación puede
 * referirse a una solicitud (elegibilidad, baremación) o a un contrato
 * (estar al corriente de pago durante la vigencia del arrendamiento).
 *
 * Guarda el origen —consulta automática o documento aportado—, la fecha y
 * la evidencia, tal como exige §5.2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('verifiable');
            $table->foreignUuid('document_id')->nullable()->constrained('documents')->restrictOnDelete();

            $table->string('verification_type', 50); // income, ownership, payment_status...
            $table->string('source', 30); // automatic_query, submitted_document
            $table->boolean('passed')->nullable();
            $table->jsonb('evidence')->nullable();
            $table->timestamp('verified_at');

            $table->timestamps();

            $table->index('verification_type');
        });

        DB::statement("ALTER TABLE verifications ADD CONSTRAINT verifications_source_check CHECK (source IN ('automatic_query', 'submitted_document'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
