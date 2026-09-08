<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adjudicación de una vivienda a una solicitud (§5.2).
 *
 * Adjudicación única (§12.3, decisión 6): índice único PARCIAL
 * `UNIQUE (property_id) WHERE status = 'active'`. Una vivienda no puede
 * tener dos adjudicaciones vivas; combinado con un cerrojo consultivo
 * (advisory lock) durante el proceso de adjudicación en la capa de
 * aplicación, resuelve la concurrencia sin coordinación externa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignUuid('property_id')->constrained('properties')->restrictOnDelete();

            $table->string('status', 20); // active, accepted, renounced, revoked, expired
            $table->timestamp('offered_at');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('renounced_at')->nullable();

            $table->timestamps();

            $table->index('application_id');
            $table->index('status');
        });

        DB::statement("ALTER TABLE awards ADD CONSTRAINT awards_status_check CHECK (status IN ('active', 'accepted', 'renounced', 'revoked', 'expired'))");
        DB::statement('CREATE UNIQUE INDEX awards_property_active_unique ON awards (property_id) WHERE status = \'active\'');
    }

    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};
