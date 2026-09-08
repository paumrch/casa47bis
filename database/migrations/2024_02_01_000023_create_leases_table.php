<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contrato de arrendamiento: vigencia, renta, fianza, firmantes (§5.2). Un
 * contrato nace de una adjudicación aceptada; se referencian `property_id`
 * y `household_id` directamente además de `award_id` porque son las
 * columnas por las que se filtra y ordena habitualmente (histórico de
 * contratos de una vivienda, contratos de una unidad).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('award_id')->unique()->constrained('awards')->restrictOnDelete();
            $table->foreignUuid('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignUuid('household_id')->constrained('households')->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('deposit_amount', 10, 2);
            $table->string('status', 20); // active, terminated, expired
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            $table->index('property_id');
            $table->index('household_id');
            $table->index('status');
        });

        DB::statement("ALTER TABLE leases ADD CONSTRAINT leases_status_check CHECK (status IN ('active', 'terminated', 'expired'))");
        DB::statement("COMMENT ON TABLE leases IS 'Retención: conservación conforme al plazo legal de contratos de arrendamiento y a efectos fiscales tras su extinción.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
