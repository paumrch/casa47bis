<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convocatoria: plazos, ámbito, cupos y baremo aplicable (§5.2).
 *
 * Borrado lógico: sí, segunda excepción de §12.3 (histórico de
 * convocatorias relevante).
 *
 * `eligibility_rule_version`/`scoring_rule_version` fijan qué versión de
 * las reglas aplica por defecto a las solicitudes de esta convocatoria;
 * cada solicitud copia su propia versión en el momento de presentarse
 * (§12.3, ver `applications`), por lo que un cambio posterior aquí no
 * afecta a solicitudes ya presentadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('reference_code')->unique();
            $table->string('name');
            $table->text('scope_description')->nullable();

            $table->string('eligibility_rule_version', 20);
            $table->string('scoring_rule_version', 20);
            $table->jsonb('quotas')->nullable(); // cupos por colectivo, si aplica

            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->string('status', 20); // draft, open, closed, resolved, cancelled

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['opens_at', 'closes_at']);
        });

        DB::statement("ALTER TABLE calls ADD CONSTRAINT calls_status_check CHECK (status IN ('draft', 'open', 'closed', 'resolved', 'cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
