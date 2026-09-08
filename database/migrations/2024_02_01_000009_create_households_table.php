<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unidad de convivencia "viva": agrupa a sus miembros y consolida ingresos.
 *
 * No confundir con la instantánea congelada en `applications` (§5.2, §12.3):
 * esta tabla refleja la composición actual, que cambia en el tiempo; la
 * solicitud conserva la que existía cuando se presentó.
 *
 * Sin borrado lógico: no está en la lista de excepción (§12.3); si una
 * unidad deja de existir como tal, se refleja con los datos vigentes, no
 * se oculta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('reference_code')->unique();
            $table->decimal('income_total', 10, 2)->nullable();

            $table->string('address_line')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->timestamps();
        });

        DB::statement("COMMENT ON TABLE households IS 'Unidades de convivencia vigentes. Retención: ligada a los expedientes que la referencian; se conserva mientras exista al menos una solicitud o contrato activo o en plazo de conservación.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
