<?php

declare(strict_types=1);

namespace App\Domains\Applications\Actions;

use App\Domains\Applications\ApplicationStatus;
use App\Domains\Applications\Exceptions\InvalidTransition;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Values\VerificationSummary;
use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Eligibility\RuleSetRegistry;
use App\Domains\Eligibility\Values\ApplicantProfile;
use App\Domains\Eligibility\Values\Money;
use App\Domains\Shared\Outbox\OutboxRecorder;
use App\Integrations\Contracts\DataVerificationGateway;
use App\Integrations\Contracts\Values\VerificationRequest;
use App\Integrations\Contracts\Values\VerificationStatus;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

/**
 * Comprobar los requisitos de una solicitud.
 *
 * ESTA ACCIÓN ES LA RESPUESTA A LA OBJECIÓN MÁS SERIA CONTRA LA ARQUITECTURA.
 *
 * Un revisor razonable dirá: «vuestra elegibilidad depende de servicios de la
 * Administración que no controláis, que exigen Red SARA y que se caen». Tiene razón. Por
 * eso la indisponibilidad **no se trata como un error**, sino como un tercer desenlace
 * previsto del negocio:
 *
 *   confirmado  → el dato queda acreditado por consulta a quien lo custodia
 *   refutado    → el requisito no se cumple, con evidencia
 *   no disponible → se pide la documentación al ciudadano y el expediente sigue
 *
 * El procedimiento administrativo NUNCA se detiene porque un tercero no responda. Si se
 * detuviera, el plazo seguiría corriendo contra el ciudadano por una avería que no es
 * suya, y eso es indefensión.
 *
 * Es también la razón de que el sistema pueda ponerse en producción antes de tener el
 * alta en la Plataforma de Intermediación: con el adaptador que declara todo como no
 * disponible, funciona por vía documental desde el primer día.
 */
final readonly class VerifyApplication
{
    /**
     * Comprobaciones exigidas, en el orden en que se piden.
     *
     * Los códigos coinciden con los criterios del conjunto de reglas: si alguien añade un
     * criterio a las reglas y olvida añadirlo aquí, la solicitud se evaluaría sin
     * comprobarlo. Una prueba compara ambas listas para que eso no ocurra.
     */
    public const REQUIRED_CHECKS = [
        'income',
        'residence',
        'property_ownership',
        'tax_compliance',
        'social_security_compliance',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private DataVerificationGateway $gateway,
        private RuleSetRegistry $rules,
        private AuditRecorder $audit,
        private OutboxRecorder $outbox,
    ) {}

    public function __invoke(string $applicationId, AuditActor $actor): VerificationSummary
    {
        // Las consultas a servicios externos se hacen ANTES de abrir la transacción.
        // Mantener una transacción abierta mientras se espera a una respuesta de red es
        // la forma más eficaz de agotar las conexiones de la base de datos.
        $application = Application::query()->whereKey($applicationId)->firstOrFail();

        $this->assertCanBeVerified($application);

        $correlationId = (string) Str::uuid7();
        $outcomes = $this->runChecks($application, $correlationId);

        return $this->db->transaction(function () use ($application, $outcomes, $actor, $correlationId): VerificationSummary {
            /** @var Application $fresh */
            $fresh = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();

            $this->assertCanBeVerified($fresh);

            $this->recordVerifications($fresh, $outcomes);

            $unavailable = array_keys(array_filter(
                $outcomes,
                static fn (array $o): bool => $o['status'] === VerificationStatus::Unavailable,
            ));

            return $unavailable === []
                ? $this->resolve($fresh, $outcomes, $actor)
                : $this->requestDocuments($fresh, $outcomes, $unavailable, $actor, $correlationId);
        });
    }

    private function assertCanBeVerified(Application $application): void
    {
        if (! $application->status->canTransitionTo(ApplicationStatus::UnderVerification)
            && $application->status !== ApplicationStatus::UnderVerification) {
            throw new InvalidTransition($application->status, ApplicationStatus::UnderVerification);
        }
    }

    /**
     * @return array<string, array{status: VerificationStatus, data: array<string, scalar|null>, reason: string|null}>
     */
    private function runChecks(Application $application, string $correlationId): array
    {
        $supported = $this->gateway->supportedChecks();
        $outcomes = [];

        foreach (self::REQUIRED_CHECKS as $check) {
            if (! in_array($check, $supported, strict: true)) {
                // Ni siquiera se intenta: el adaptador ya ha dicho que no sabe resolverla.
                // Se pide la documentación desde el principio en lugar de hacer una
                // llamada condenada a fallar y esperar su tiempo de espera.
                $outcomes[$check] = [
                    'status' => VerificationStatus::Unavailable,
                    'data' => [],
                    'reason' => 'La comprobación automática de este dato no está disponible.',
                ];

                continue;
            }

            $outcome = $this->gateway->verify(new VerificationRequest(
                check: $check,
                subjectDocumentNumber: $application->applicant_document_hash,
                purpose: 'Verificación de requisitos de acceso a vivienda de alquiler asequible',
                consentReference: (string) $application->call_id,
                correlationId: $correlationId,
            ));

            $outcomes[$check] = [
                'status' => $outcome->status,
                'data' => $outcome->data,
                'reason' => $outcome->reason,
            ];
        }

        return $outcomes;
    }

    /**
     * @param  array<string, array{status: VerificationStatus, data: array<string, scalar|null>, reason: string|null}>  $outcomes
     */
    private function recordVerifications(Application $application, array $outcomes): void
    {
        $rows = [];

        foreach ($outcomes as $check => $outcome) {
            $rows[] = [
                'id' => (string) Str::uuid7(),
                'verifiable_type' => Application::class,
                'verifiable_id' => $application->id,
                'document_id' => null,
                'verification_type' => $check,
                'source' => 'automatic_query',
                'passed' => match ($outcome['status']) {
                    VerificationStatus::Confirmed => true,
                    VerificationStatus::Refuted => false,
                    // No es ni sí ni no: es que no se sabe. Guardar `false` aquí sería
                    // registrar que el ciudadano no cumple cuando lo cierto es que no se
                    // ha podido comprobar.
                    VerificationStatus::Unavailable => null,
                },
                'evidence' => json_encode(
                    ['data' => $outcome['data'], 'reason' => $outcome['reason']],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                ),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->db->table('verifications')->insert($rows);
    }

    /**
     * @param  array<string, array{status: VerificationStatus, data: array<string, scalar|null>, reason: string|null}>  $outcomes
     * @param  list<string>  $unavailable
     */
    private function requestDocuments(
        Application $application,
        array $outcomes,
        array $unavailable,
        AuditActor $actor,
        string $correlationId,
    ): VerificationSummary {
        $this->transitionTo($application, ApplicationStatus::AwaitingApplicant, $actor, [
            'unavailable_checks' => $unavailable,
            'correlation_id' => $correlationId,
        ]);

        $this->outbox->publish(
            aggregateType: 'application',
            aggregateId: (string) $application->id,
            eventName: 'application.documents_required',
            payload: [
                'application_id' => $application->id,
                'checks' => $unavailable,
            ],
        );

        return new VerificationSummary(
            status: ApplicationStatus::AwaitingApplicant,
            outcomes: $this->outcomeCodes($outcomes),
            documentsRequired: $unavailable,
            eligibility: null,
        );
    }

    /**
     * @param  array<string, array{status: VerificationStatus, data: array<string, scalar|null>, reason: string|null}>  $outcomes
     */
    private function resolve(Application $application, array $outcomes, AuditActor $actor): VerificationSummary
    {
        // El perfil se construye con los datos VERIFICADOS, no con lo que declaró el
        // ciudadano. Es la diferencia entre comprobar y creer.
        $profile = $this->buildProfile($application, $outcomes);

        $ruleSet = $this->rules->version($application->eligibility_rule_version);
        $result = $ruleSet->evaluate($profile);

        $target = $result->isEligible() ? ApplicationStatus::Eligible : ApplicationStatus::Ineligible;

        $this->transitionTo($application, $target, $actor, $result->toAuditTrail());

        $this->outbox->publish(
            aggregateType: 'application',
            aggregateId: (string) $application->id,
            eventName: $result->isEligible() ? 'application.eligible' : 'application.ineligible',
            payload: [
                'application_id' => $application->id,
                'motivation' => $result->motivation(),
            ],
        );

        return new VerificationSummary(
            status: $target,
            outcomes: $this->outcomeCodes($outcomes),
            documentsRequired: [],
            eligibility: $result,
        );
    }

    /**
     * @param  array<string, array{status: VerificationStatus, data: array<string, scalar|null>, reason: string|null}>  $outcomes
     */
    private function buildProfile(Application $application, array $outcomes): ApplicantProfile
    {
        $income = $outcomes['income']['data']['annual_net_income'] ?? 0;

        return new ApplicantProfile(
            householdSize: max(1, $application->household_snapshot_member_count),
            annualNetIncome: Money::fromEuros(is_string($income) ? $income : (int) $income),
            hasLegalResidence: $outcomes['residence']['status'] === VerificationStatus::Confirmed,
            ownsProperty: $outcomes['property_ownership']['status'] === VerificationStatus::Refuted,
            ownedPropertyIsUnusable: (bool) ($outcomes['property_ownership']['data']['unusable'] ?? false),
            isCurrentWithTaxAuthority: $outcomes['tax_compliance']['status'] === VerificationStatus::Confirmed,
            isCurrentWithSocialSecurity: $outcomes['social_security_compliance']['status'] === VerificationStatus::Confirmed,
        );
    }

    /** @param array<string, mixed> $metadata */
    private function transitionTo(Application $application, ApplicationStatus $target, AuditActor $actor, array $metadata): void
    {
        $from = $application->status;
        $now = now();

        // Se pasa siempre por «en comprobación», aunque la resolución sea inmediata: el
        // histórico del expediente debe reflejar el camino real, no un salto.
        if ($from !== ApplicationStatus::UnderVerification) {
            $this->writeTransition($application, $from, ApplicationStatus::UnderVerification, $actor, $now);
            $from = ApplicationStatus::UnderVerification;
        }

        $this->writeTransition($application, $from, $target, $actor, $now);

        $application->forceFill(['status' => $target])->save();

        $this->audit->record(
            action: 'application.verified',
            entityType: 'application',
            entityId: (string) $application->id,
            actor: $actor,
            metadata: ['to' => $target->value] + $metadata,
        );
    }

    private function writeTransition(
        Application $application,
        ApplicationStatus $from,
        ApplicationStatus $to,
        AuditActor $actor,
        \DateTimeInterface $at,
    ): void {
        $this->db->table('application_transitions')->insert([
            'id' => (string) Str::uuid7(),
            'application_id' => $application->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'reason' => null,
            'occurred_at' => $at,
        ]);
    }

    /**
     * @param  array<string, array{status: VerificationStatus, data: array<string, scalar|null>, reason: string|null}>  $outcomes
     * @return array<string, string>
     */
    private function outcomeCodes(array $outcomes): array
    {
        return array_map(static fn (array $o): string => $o['status']->value, $outcomes);
    }
}
