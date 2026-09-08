<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vivienda concreta. Superficie, dormitorios, planta, accesibilidad,
 * anejos, renta, estado (§5.2).
 *
 * Borrado lógico: sí (`deleted_at`), es una de las dos excepciones de
 * §12.3 —el histórico de una vivienda importa aunque deje de ofertarse.
 *
 * `status` es texto con `CHECK`, no ENUM nativo (§12.3): añadir un valor no
 * debe requerir una migración de tipo.
 *
 * Índices pensados para el catálogo filtrado real: dormitorios, renta,
 * accesibilidad, municipio, estado; y `pg_trgm` sobre municipio/dirección
 * para tolerancia a erratas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('development_id')->constrained('developments')->restrictOnDelete();

            $table->string('reference_code')->unique();
            $table->unsignedTinyInteger('bedrooms');
            $table->unsignedTinyInteger('bathrooms');
            $table->decimal('surface_m2', 6, 2);
            $table->string('floor', 10)->nullable();
            $table->boolean('has_elevator')->default(false);
            $table->boolean('accessible')->default(false);
            $table->boolean('has_garage')->default(false);
            $table->boolean('has_storage_room')->default(false);

            $table->decimal('monthly_rent', 10, 2);
            $table->string('status', 20); // available, reserved, awarded, leased, unavailable

            $table->string('address_line')->nullable();
            $table->string('municipality');
            $table->string('province');
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('bedrooms');
            $table->index('monthly_rent');
            $table->index('accessible');
            $table->index('status');
            $table->index('municipality');
            $table->index(['latitude', 'longitude']);
        });

        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_status_check CHECK (status IN ('available', 'reserved', 'awarded', 'leased', 'unavailable'))");
        DB::statement('CREATE INDEX properties_municipality_trgm_idx ON properties USING GIN (municipality gin_trgm_ops)');
        DB::statement('CREATE INDEX properties_address_line_trgm_idx ON properties USING GIN (address_line gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
