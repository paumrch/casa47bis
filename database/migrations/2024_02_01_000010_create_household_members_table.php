<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo persona–unidad de convivencia: parentesco, titularidad y
 * circunstancias que afectan a la baremación.
 *
 * `disability_status` y `gender_violence_victim_status` son datos de
 * categoría especial (art. 9 RGPD, §5.4): se cifran a nivel de aplicación
 * (la columna guarda ciphertext como `text`, nunca el dato en claro) y su
 * lectura exige motivo registrado en `audit_events`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained('persons')->restrictOnDelete();

            $table->string('relationship'); // parentesco con el/la titular
            $table->boolean('is_holder')->default(false);
            $table->boolean('dependent_minor')->default(false);

            $table->text('disability_status')->nullable();
            $table->text('gender_violence_victim_status')->nullable();

            $table->timestamps();

            $table->unique(['household_id', 'person_id']);
        });

        DB::statement("COMMENT ON COLUMN household_members.disability_status IS 'Dato de categoría especial (art. 9 RGPD). Ciphertext cifrado a nivel de aplicación, no en la base. Acceso restringido a rol específico y auditoría reforzada con motivo obligatorio.'");
        DB::statement("COMMENT ON COLUMN household_members.gender_violence_victim_status IS 'Dato de categoría especial (art. 9 RGPD). Ciphertext cifrado a nivel de aplicación, no en la base. Acceso restringido a rol específico y auditoría reforzada con motivo obligatorio.'");
        DB::statement("COMMENT ON TABLE household_members IS 'Retención: igual que households; los campos de categoría especial siguen además la política de conservación específica del baremo que los recoge.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
