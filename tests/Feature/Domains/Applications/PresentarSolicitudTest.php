<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Applications;

use App\Domains\Applications\Actions\SubmitApplication;
use App\Domains\Applications\ApplicationStatus;
use App\Domains\Applications\Exceptions\InvalidTransition;
use App\Domains\Applications\Models\Application;
use App\Domains\Audit\AuditActor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Presentar una solicitud: la operación que sostiene toda la arquitectura.
 *
 * La prueba que de verdad importa es `si_falla_el_ultimo_paso_no_queda_nada_a_medias`.
 * Es la que demuestra que no hace falta coordinación distribuida para que un cambio de
 * estado, su auditoría y su notificación sean atómicos.
 */
final class PresentarSolicitudTest extends TestCase
{
    use RefreshDatabase;

    private function crearSolicitudEnBorrador(): string
    {
        $callId = (string) Str::uuid7();
        $householdId = (string) Str::uuid7();

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

        $application = Application::query()->create([
            'call_id' => $callId,
            'household_id' => $householdId,
            'applicant_document_hash' => hash('sha256', Str::random(12)),
            'status' => ApplicationStatus::Draft,
            'eligibility_rule_version' => '2026.1',
            'scoring_rule_version' => '2026.1',
            'submitted_at' => now(),
            'household_snapshot_reference_code' => 'UC-SNAP',
            'household_snapshot_member_count' => 0,
            'household_snapshot_members' => [],
        ]);

        return (string) $application->id;
    }

    /** @return array<int, array<string, mixed>> */
    private function miembros(): array
    {
        return [
            ['role' => 'titular', 'birth_year' => 1988],
            ['role' => 'conyuge', 'birth_year' => 1990],
            ['role' => 'menor', 'birth_year' => 2019],
        ];
    }

    private function accion(): SubmitApplication
    {
        return app(SubmitApplication::class);
    }

    #[Test]
    public function presentar_cambia_el_estado_y_congela_la_unidad_de_convivencia(): void
    {
        $id = $this->crearSolicitudEnBorrador();

        $application = ($this->accion())($id, $this->miembros(), AuditActor::citizen((string) Str::uuid7(), '192.0.2.10'));

        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertSame(3, $application->household_snapshot_member_count);
        $this->assertCount(3, $application->household_snapshot_members);
    }

    #[Test]
    public function presentar_deja_traza_en_los_tres_registros(): void
    {
        $id = $this->crearSolicitudEnBorrador();

        ($this->accion())($id, $this->miembros(), AuditActor::citizen((string) Str::uuid7()));

        $this->assertDatabaseHas('application_transitions', [
            'application_id' => $id,
            'from_status' => 'draft',
            'to_status' => 'submitted',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'application.submitted',
            'entity_type' => 'application',
            'entity_id' => $id,
        ]);

        $this->assertDatabaseHas('outbox_events', [
            'aggregate_id' => $id,
            'event_name' => 'application.submitted',
            'delivered_at' => null,
        ]);
    }

    #[Test]
    public function no_se_puede_presentar_dos_veces(): void
    {
        $id = $this->crearSolicitudEnBorrador();

        ($this->accion())($id, $this->miembros(), AuditActor::citizen((string) Str::uuid7()));

        $this->expectException(InvalidTransition::class);

        ($this->accion())($id, $this->miembros(), AuditActor::citizen((string) Str::uuid7()));
    }

    #[Test]
    public function el_mensaje_de_error_explica_que_transiciones_si_serian_validas(): void
    {
        $id = $this->crearSolicitudEnBorrador();
        ($this->accion())($id, $this->miembros(), AuditActor::citizen((string) Str::uuid7()));

        try {
            ($this->accion())($id, $this->miembros(), AuditActor::citizen((string) Str::uuid7()));
            $this->fail('Se esperaba una transición inválida.');
        } catch (InvalidTransition $e) {
            $this->assertStringContainsString('under_verification', $e->getMessage());
        }
    }

    /**
     * LA PRUEBA CENTRAL.
     *
     * Se rompe el último paso de la operación —la escritura en la bandeja de salida— y
     * se comprueba que NADA de lo anterior queda confirmado: ni el cambio de estado, ni
     * la transición, ni la auditoría.
     *
     * Sin esta garantía existiría el estado en el que una solicitud consta presentada y
     * jamás se notificará a nadie. Es un fallo silencioso: nadie se entera hasta que el
     * ciudadano reclama fuera de plazo.
     */
    #[Test]
    public function si_falla_el_ultimo_paso_no_queda_nada_a_medias(): void
    {
        $id = $this->crearSolicitudEnBorrador();

        // Se provoca un fallo REAL en el último paso en lugar de suplantar una clase:
        // sin la tabla, la escritura en la bandeja de salida revienta con un error de
        // base de datos, que es exactamente lo que ocurriría ante un problema de
        // almacenamiento en producción. Así se ejercita el código real, no un doble.
        Schema::drop('outbox_events');

        try {
            ($this->accion())($id, $this->miembros(), AuditActor::citizen((string) Str::uuid7()));
            $this->fail('Se esperaba que la operación fallara.');
        } catch (QueryException) {
            // Esperado.
        }

        $this->assertSame(
            'draft',
            DB::table('applications')->where('id', $id)->value('status'),
            'La solicitud no puede quedar presentada si la operación no se completó.',
        );

        $this->assertDatabaseMissing('application_transitions', ['application_id' => $id]);
        $this->assertDatabaseMissing('audit_events', ['entity_id' => $id]);
    }
}
