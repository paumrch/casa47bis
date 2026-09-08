<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de cambios de estado de una solicitud: de dónde, a dónde,
 * quién y por qué (§12.3, decisión 4). Complementa el `CHECK` de
 * `applications.status`, que sólo garantiza que el valor final es válido,
 * no de dónde vino ni quién lo cambió.
 *
 * `actor_type`/`actor_id` es un morph porque el actor puede ser un
 * `StaffUser`, una `Account` (retirada por la propia solicitante) o el
 * propio sistema (transición automática).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();

            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->nullableUuidMorphs('actor');
            $table->text('reason')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['application_id', 'occurred_at']);
        });

        DB::statement("COMMENT ON TABLE application_transitions IS 'Retención: igual que applications, forma parte del expediente; sólo inserción en la práctica, no se editan transiciones ya registradas.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('application_transitions');
    }
};
