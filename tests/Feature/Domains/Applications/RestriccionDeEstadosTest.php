<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Applications;

use App\Domains\Applications\ApplicationStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La enumeración del dominio y la restricción de la base dicen lo mismo.
 *
 * POR QUÉ EXISTE ESTA PRUEBA
 *
 * Se escribió después de que el fallo ocurriera de verdad. La enumeración declaraba
 * trece estados y la restricción CHECK de la migración enumeraba otros nueve, escritos a
 * mano en otro momento. La aplicación intentaba guardar «draft» y PostgreSQL lo
 * rechazaba.
 *
 * Es el fallo clásico de tener dos fuentes de verdad para la misma lista. Se ha
 * corregido derivando la restricción de la enumeración, y esta prueba es el cinturón de
 * seguridad: si alguien añade un estado al procedimiento sin migrar la base, la
 * integración continua se pone en rojo antes de que llegue a producción.
 *
 * El coste de la prueba son veinte líneas. El coste de no tenerla es un expediente que
 * no se puede guardar, descubierto por un ciudadano a las once de la noche del último
 * día de plazo.
 */
final class RestriccionDeEstadosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_restriccion_check_acepta_exactamente_los_estados_del_dominio(): void
    {
        $definition = DB::selectOne(
            "SELECT pg_get_constraintdef(oid) AS def
               FROM pg_constraint
              WHERE conrelid = 'applications'::regclass
                AND conname = 'applications_status_check'"
        );

        $this->assertNotNull($definition, 'No existe la restricción applications_status_check.');

        preg_match_all("/'([a-z_]+)'/", (string) $definition->def, $matches);

        $this->assertEqualsCanonicalizing(
            ApplicationStatus::values(),
            array_unique($matches[1]),
            'La enumeración del dominio y la restricción de la base han divergido. '.
            'Añade una migración que actualice la restricción.',
        );
    }

    #[Test]
    public function la_base_rechaza_un_estado_que_el_procedimiento_no_contempla(): void
    {
        $this->expectException(QueryException::class);

        DB::table('applications')->insert([
            'id' => (string) Str::uuid7(),
            'call_id' => (string) Str::uuid7(),
            'household_id' => (string) Str::uuid7(),
            'applicant_document_hash' => str_repeat('a', 64),
            'status' => 'estado_inventado',
            'eligibility_rule_version' => '2026.1',
            'scoring_rule_version' => '2026.1',
            'submitted_at' => now(),
            'household_snapshot_reference_code' => 'UC-1',
            'household_snapshot_member_count' => 1,
            'household_snapshot_members' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
