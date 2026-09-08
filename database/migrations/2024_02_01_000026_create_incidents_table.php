<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Incidencia de mantenimiento o consulta sobre una vivienda o contrato
 * (§5.2). `lease_id` es nullable: puede reportarse una incidencia antes de
 * la firma del contrato o sobre zonas comunes sin contrato asociado.
 *
 * `reported_by_type`/`reported_by_id` es un morph: puede reportar tanto la
 * ciudadanía (`Account`) como personal gestor (`StaffUser`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignUuid('lease_id')->nullable()->constrained('leases')->restrictOnDelete();
            $table->nullableUuidMorphs('reported_by');

            $table->string('category', 30); // maintenance, inquiry
            $table->string('title');
            $table->text('description');
            $table->string('status', 20); // open, in_progress, resolved, closed
            $table->string('priority', 10)->default('medium'); // low, medium, high, urgent

            $table->timestamps();

            $table->index('property_id');
            $table->index('status');
            $table->index('category');
        });

        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_status_check CHECK (status IN ('open', 'in_progress', 'resolved', 'closed'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_priority_check CHECK (priority IN ('low', 'medium', 'high', 'urgent'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
