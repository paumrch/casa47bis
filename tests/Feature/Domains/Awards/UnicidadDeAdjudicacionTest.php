<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Awards;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Una vivienda no puede adjudicarse dos veces. Nunca.
 *
 * POR QUÉ ESTA PRUEBA EXISTE Y POR QUÉ ESTÁ ESCRITA ASÍ
 *
 * Adjudicar dos veces la misma vivienda no es un error de software: es un acto
 * administrativo nulo, dos personas con una expectativa de derecho sobre el mismo
 * inmueble y un problema que se resuelve en los tribunales.
 *
 * Es exactamente el tipo de fallo que aparece bajo concurrencia —dos gestores
 * resolviendo a la vez, o un reintento de la cola— y que no se manifiesta jamás en
 * pruebas secuenciales.
 *
 * La garantía NO está en el código de la aplicación. Está en un índice único parcial
 * de PostgreSQL: `UNIQUE (property_id) WHERE status = 'active'`. Esa distinción es la
 * tesis de este proyecto en miniatura: la restricción vive en la base de datos, donde
 * resiste a la concurrencia, a un error de programación y a una escritura manual. Una
 * comprobación en PHP del tipo «si ya existe, no insertes» tiene una ventana de carrera
 * entre la lectura y la escritura, y bajo carga esa ventana se abre.
 */
final class UnicidadDeAdjudicacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Esta garantía la aporta un índice parcial de PostgreSQL.');
        }
    }

    /** Construye la cadena mínima de datos necesaria: promoción → vivienda. */
    private function crearVivienda(): string
    {
        $developmentId = (string) Str::uuid7();
        $propertyId = (string) Str::uuid7();

        DB::table('developments')->insert([
            'id' => $developmentId,
            'name' => 'Promoción de prueba',
            'construction_status' => 'completed',
            'municipality' => 'Madrid',
            'province' => 'Madrid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('properties')->insert([
            'id' => $propertyId,
            'development_id' => $developmentId,
            'reference_code' => 'VIV-'.Str::random(8),
            'bedrooms' => 2,
            'bathrooms' => 1,
            'surface_m2' => 65.00,
            'monthly_rent' => 550.00,
            'status' => 'available',
            'municipality' => 'Madrid',
            'province' => 'Madrid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $propertyId;
    }

    /** Convocatoria → unidad de convivencia → solicitud presentada. */
    private function crearSolicitud(): string
    {
        $callId = (string) Str::uuid7();
        $householdId = (string) Str::uuid7();
        $applicationId = (string) Str::uuid7();

        DB::table('calls')->insert([
            'id' => $callId,
            'reference_code' => 'CONV-'.Str::random(8),
            'name' => 'Convocatoria de prueba',
            'eligibility_rule_version' => '2026.1',
            'scoring_rule_version' => '2026.1',
            'opens_at' => now()->subMonth(),
            'closes_at' => now()->addMonth(),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('households')->insert([
            'id' => $householdId,
            'reference_code' => 'UC-'.Str::random(8),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('applications')->insert([
            'id' => $applicationId,
            'call_id' => $callId,
            'household_id' => $householdId,
            'applicant_document_hash' => hash('sha256', Str::random(12)),
            'status' => 'scored',
            'eligibility_rule_version' => '2026.1',
            'scoring_rule_version' => '2026.1',
            'submitted_at' => now(),
            'household_snapshot_reference_code' => 'UC-SNAP',
            'household_snapshot_member_count' => 2,
            'household_snapshot_members' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $applicationId;
    }

    private function adjudicar(string $propertyId, string $applicationId, string $status): void
    {
        DB::table('awards')->insert([
            'id' => (string) Str::uuid7(),
            'application_id' => $applicationId,
            'property_id' => $propertyId,
            'status' => $status,
            'offered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function la_base_de_datos_rechaza_una_segunda_adjudicacion_activa(): void
    {
        $propertyId = $this->crearVivienda();

        $this->adjudicar($propertyId, $this->crearSolicitud(), 'active');

        // Otra solicitud distinta, la misma vivienda: es el escenario real de dos
        // gestores resolviendo a la vez.
        $this->expectException(UniqueConstraintViolationException::class);

        $this->adjudicar($propertyId, $this->crearSolicitud(), 'active');
    }

    #[Test]
    public function tras_una_renuncia_la_vivienda_vuelve_a_estar_disponible(): void
    {
        $propertyId = $this->crearVivienda();

        $this->adjudicar($propertyId, $this->crearSolicitud(), 'active');

        DB::table('awards')->where('property_id', $propertyId)->update(['status' => 'renounced']);

        // El índice es PARCIAL: sólo restringe las adjudicaciones activas. Una vivienda
        // renunciada puede volver a adjudicarse, que es justo lo que exige el
        // procedimiento cuando alguien rechaza la vivienda que se le ofreció.
        $this->adjudicar($propertyId, $this->crearSolicitud(), 'active');

        $this->assertSame(
            1,
            DB::table('awards')->where('property_id', $propertyId)->where('status', 'active')->count(),
        );
        $this->assertSame(
            2,
            DB::table('awards')->where('property_id', $propertyId)->count(),
            'El histórico se conserva: la renuncia sigue en el expediente.',
        );
    }
}
