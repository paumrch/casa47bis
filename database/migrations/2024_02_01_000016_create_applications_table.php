<?php

use App\Domains\Applications\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitud. La entidad central del sistema (§5.2).
 *
 * INSTANTÁNEA CONGELADA (§5.2, §12.3): esta tabla NO referencia los datos
 * vivos de `households`/`household_members` para lo que determina el
 * derecho. Los copia en el momento de presentar, como columnas
 * (`household_snapshot_*`) más un `jsonb` (`household_snapshot_members`)
 * con el detalle de cada miembro tal como constaba entonces —incluidos, si
 * el baremo los pondera, los indicadores de categoría especial ya cifrados
 * en origen. Motivo: la unidad de convivencia cambia en el tiempo; sin la
 * instantánea, resolver un recurso administrativo años después es
 * irresoluble porque no se podría demostrar con qué datos se evaluó la
 * solicitud. Es trazabilidad jurídica, no desnormalización por rendimiento.
 * `household_id` se conserva como referencia de continuidad (para navegar
 * al ciudadano actual), pero ninguna regla de elegibilidad o baremación
 * debe leer de `households` en vivo para una solicitud ya presentada.
 *
 * `eligibility_rule_version` y `scoring_rule_version`: versión de las
 * reglas vigentes al presentar. Permiten reconstruir cómo se evaluó una
 * solicitud antigua aunque el baremo haya cambiado después (§12.3).
 *
 * `applicant_document_hash`: hash del documento (NIF/NIE) de la persona
 * titular en el momento de presentar. Junto con `call_id` sostiene la
 * unicidad de negocio (una unidad no puede solicitar dos veces a la misma
 * convocatoria) sin tener que decifrar ni comparar datos en claro.
 *
 * `status`: texto con `CHECK`, no ENUM nativo (§12.3). El histórico de
 * cambios de estado vive en `application_transitions`.
 *
 * Sin borrado lógico: el expediente no se borra (§12.3, decisión 14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('call_id')->constrained('calls')->restrictOnDelete();
            $table->foreignUuid('household_id')->constrained('households')->restrictOnDelete();

            $table->char('applicant_document_hash', 64);
            $table->string('status', 20);
            $table->string('eligibility_rule_version', 20);
            $table->string('scoring_rule_version', 20);
            $table->timestamp('submitted_at');

            // Instantánea de la unidad de convivencia en el momento de presentar.
            $table->string('household_snapshot_reference_code');
            $table->unsignedSmallInteger('household_snapshot_member_count');
            $table->decimal('household_snapshot_income_total', 10, 2)->nullable();
            $table->string('household_snapshot_municipality')->nullable();
            $table->jsonb('household_snapshot_members'); // detalle por miembro: parentesco, titularidad, circunstancias

            $table->timestamps();

            $table->unique(['call_id', 'applicant_document_hash']);
            $table->index(['call_id', 'status']);
            $table->index('status');
            $table->index('submitted_at');
        });

        // La lista de estados válidos se DERIVA de la enumeración del dominio, no se
        // copia. Copiarla fue el primer error de este fichero: la enumeración y la
        // restricción se separaron y la aplicación intentó guardar un estado que la
        // base rechazaba. Con una única fuente de verdad eso no puede volver a pasar,
        // y una prueba (RestriccionDeEstadosTest) comprueba que siguen coincidiendo.
        $statuses = implode(', ', array_map(
            static fn (string $value): string => "'".$value."'",
            ApplicationStatus::values(),
        ));

        DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN ({$statuses}))");
        DB::statement("COMMENT ON COLUMN applications.household_snapshot_members IS 'Instantánea inmutable de los miembros de la unidad al presentar la solicitud. No se actualiza aunque household_members cambie después. Puede contener ciphertext de datos de categoría especial (art. 9 RGPD).'");
        DB::statement("COMMENT ON TABLE applications IS 'Retención: conservación conforme al plazo de expedientes administrativos; no se elimina, se archiva o suprime según política de conservación (§12.3, decisión 14 — sin borrado lógico en el expediente).'");
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
