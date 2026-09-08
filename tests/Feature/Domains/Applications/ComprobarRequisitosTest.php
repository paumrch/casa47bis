<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Applications;

use App\Domains\Applications\Actions\VerifyApplication;
use App\Domains\Applications\ApplicationStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Eligibility\RuleSetRegistry;
use App\Domains\Shared\Outbox\OutboxRecorder;
use App\Integrations\Testing\FakeDataVerificationGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprobación de requisitos, incluido el caso que casi nunca se prueba.
 *
 * La prueba central de esta clase es `si_la_administracion_no_responde_el_expediente_no_se_detiene`.
 * Es la respuesta a la objeción más seria contra esta arquitectura: que depende de
 * servicios de la Administración que no controlamos y que se caen.
 */
final class ComprobarRequisitosTest extends TestCase
{
    use RefreshDatabase;

    private function crearSolicitudPresentada(int $miembros = 3): string
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
            'status' => ApplicationStatus::Submitted,
            'eligibility_rule_version' => '2026.1',
            'scoring_rule_version' => '2026.1',
            'submitted_at' => now(),
            'household_snapshot_reference_code' => 'UC-SNAP',
            'household_snapshot_member_count' => $miembros,
            'household_snapshot_members' => [],
        ]);

        return (string) $application->id;
    }

    private function accion(FakeDataVerificationGateway $gateway): VerifyApplication
    {
        return new VerifyApplication(
            DB::connection(),
            $gateway,
            new RuleSetRegistry,
            new AuditRecorder(DB::connection()),
            new OutboxRecorder(DB::connection()),
        );
    }

    private function pasarelaQueTodoConfirma(string $income = '30000'): FakeDataVerificationGateway
    {
        return (new FakeDataVerificationGateway)
            ->willConfirm('income', ['annual_net_income' => $income])
            ->willConfirm('residence')
            ->willConfirm('property_ownership', ['unusable' => false])
            ->willConfirm('tax_compliance')
            ->willConfirm('social_security_compliance');
    }

    #[Test]
    public function una_solicitud_que_cumple_todo_queda_admitida(): void
    {
        $id = $this->crearSolicitudPresentada();

        $summary = ($this->accion($this->pasarelaQueTodoConfirma()))($id, AuditActor::system());

        $this->assertSame(ApplicationStatus::Eligible, $summary->status);
        $this->assertFalse($summary->needsDocuments());
        $this->assertNotNull($summary->eligibility);
        $this->assertTrue($summary->eligibility->isEligible());
        $this->assertDatabaseHas('applications', ['id' => $id, 'status' => 'eligible']);
    }

    #[Test]
    public function unos_ingresos_fuera_del_intervalo_excluyen_con_motivacion(): void
    {
        $id = $this->crearSolicitudPresentada();

        $summary = ($this->accion($this->pasarelaQueTodoConfirma('90000')))($id, AuditActor::system());

        $this->assertSame(ApplicationStatus::Ineligible, $summary->status);
        $this->assertNotNull($summary->eligibility);
        $this->assertStringContainsString('superan el máximo', $summary->eligibility->motivation());
    }

    /**
     * LA PRUEBA CENTRAL.
     *
     * Cuando un servicio de la Administración no responde, el expediente NO se queda
     * atascado ni se resuelve a ciegas: pasa a requerir documentación y sigue vivo. El
     * plazo no puede correr contra el ciudadano por una avería que no es suya.
     */
    #[Test]
    public function si_la_administracion_no_responde_el_expediente_no_se_detiene(): void
    {
        $id = $this->crearSolicitudPresentada();

        $gateway = $this->pasarelaQueTodoConfirma()
            ->willBeUnavailable('income', 'La AEAT no responde.');

        $summary = ($this->accion($gateway))($id, AuditActor::system());

        $this->assertSame(ApplicationStatus::AwaitingApplicant, $summary->status);
        $this->assertTrue($summary->needsDocuments());
        $this->assertSame(['income'], $summary->documentsRequired);
        $this->assertNull(
            $summary->eligibility,
            'No puede resolverse la elegibilidad sin haber comprobado todos los requisitos.',
        );
    }

    #[Test]
    public function una_comprobacion_no_disponible_no_se_registra_como_incumplida(): void
    {
        $id = $this->crearSolicitudPresentada();

        ($this->accion($this->pasarelaQueTodoConfirma()->willBeUnavailable('tax_compliance')))($id, AuditActor::system());

        $fila = DB::table('verifications')
            ->where('verifiable_id', $id)
            ->where('verification_type', 'tax_compliance')
            ->first();

        $this->assertNotNull($fila);
        $this->assertNull(
            $fila->passed,
            'Guardar «false» sería afirmar que el ciudadano no cumple, cuando lo cierto '.
            'es que no se ha podido comprobar. No es lo mismo.',
        );
    }

    #[Test]
    public function el_adaptador_sin_integracion_manda_todo_a_via_documental(): void
    {
        $id = $this->crearSolicitudPresentada();

        // Ninguna comprobación soportada: es el estado real antes del alta en la
        // Plataforma de Intermediación.
        $summary = ($this->accion(new FakeDataVerificationGateway(supported: [])))($id, AuditActor::system());

        $this->assertSame(ApplicationStatus::AwaitingApplicant, $summary->status);
        $this->assertEqualsCanonicalizing(VerifyApplication::REQUIRED_CHECKS, $summary->documentsRequired);
    }

    #[Test]
    public function no_se_llama_a_la_pasarela_para_comprobaciones_que_no_soporta(): void
    {
        $id = $this->crearSolicitudPresentada();

        $gateway = new FakeDataVerificationGateway(supported: ['income']);
        $gateway->willConfirm('income', ['annual_net_income' => '30000']);

        ($this->accion($gateway))($id, AuditActor::system());

        $this->assertCount(
            1,
            $gateway->received,
            'Llamar a un servicio que ya ha dicho que no sabe resolver algo sólo gasta '.
            'tiempo de espera.',
        );
    }

    #[Test]
    public function el_expediente_registra_el_paso_por_en_comprobacion(): void
    {
        $id = $this->crearSolicitudPresentada();

        ($this->accion($this->pasarelaQueTodoConfirma()))($id, AuditActor::system());

        $transiciones = DB::table('application_transitions')
            ->where('application_id', $id)
            ->orderBy('occurred_at')
            ->pluck('to_status')
            ->all();

        $this->assertSame(
            ['under_verification', 'eligible'],
            $transiciones,
            'El histórico debe reflejar el camino real del expediente, no un salto.',
        );
    }

    #[Test]
    public function cada_resolucion_encola_su_notificacion(): void
    {
        $id = $this->crearSolicitudPresentada();

        ($this->accion($this->pasarelaQueTodoConfirma()))($id, AuditActor::system());

        $this->assertDatabaseHas('outbox_events', [
            'aggregate_id' => $id,
            'event_name' => 'application.eligible',
            'delivered_at' => null,
        ]);
    }

    #[Test]
    public function las_comprobaciones_exigidas_coinciden_con_los_criterios_de_las_reglas(): void
    {
        // Si alguien añade un criterio a las reglas y olvida añadirlo a la lista de
        // comprobaciones, la solicitud se evaluaría sin comprobarlo. Esta prueba lo
        // impide.
        $codigosDeLasReglas = array_map(
            static fn (array $r): string => $r['code'],
            (new RuleSetRegistry)->current()->describe(),
        );

        $this->assertEqualsCanonicalizing(
            $codigosDeLasReglas,
            VerifyApplication::REQUIRED_CHECKS,
            'Las reglas de elegibilidad y las comprobaciones que se ejecutan han divergido.',
        );
    }
}
