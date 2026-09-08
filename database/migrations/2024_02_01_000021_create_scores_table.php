<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baremación: puntuación total y desglose por criterio, con la versión del
 * baremo aplicada (§5.2). Una solicitud tiene una única puntuación vigente;
 * recalcular actualiza la fila, no crea una nueva (el histórico de por qué
 * cambió vive en auditoría, no aquí).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->unique()->constrained('applications')->cascadeOnDelete();

            $table->string('scoring_rule_version', 20);
            $table->decimal('total_score', 6, 2);
            $table->jsonb('breakdown'); // puntuación por criterio del baremo
            $table->timestamp('calculated_at');

            $table->timestamps();

            $table->index('total_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
