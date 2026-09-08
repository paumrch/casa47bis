<?php

declare(strict_types=1);

namespace App\Domains\Applications\Actions;

use App\Domains\Applications\ApplicationStatus;
use App\Domains\Applications\Exceptions\InvalidTransition;
use App\Domains\Applications\Models\Application;
use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Shared\Outbox\OutboxRecorder;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

/**
 * Presentar una solicitud.
 *
 * ESTA CLASE ES EL EJEMPLO CANÓNICO DE TODA LA ARQUITECTURA. Todo lo que ocurre al
 * presentar una solicitud sucede en UNA transacción:
 *
 *   1. Se toma la fila en exclusiva, para que dos pulsaciones del botón no presenten dos
 *      veces.
 *   2. Se valida la transición contra la máquina de estados.
 *   3. Se congela la instantánea de la unidad de convivencia.
 *   4. Se fija la fecha de presentación, que es el dato jurídicamente relevante.
 *   5. Se registra la transición en el histórico del expediente.
 *   6. Se escribe el evento de auditoría, encadenado.
 *   7. Se encola el evento de la bandeja de salida que disparará la notificación.
 *
 * O se confirman los siete pasos, o no se confirma ninguno. No existe el estado
 * intermedio en el que la solicitud consta presentada pero nadie la notificará, ni aquel
 * en el que se notifica algo que no llegó a registrarse.
 *
 * Conseguir esto con un CRM y una plataforma de automatización separados exige
 * coordinación distribuida. Aquí es una transacción de base de datos, que es la
 * herramienta que lleva cuarenta años resolviendo exactamente este problema.
 *
 * LO QUE NO OCURRE AQUÍ
 *
 * No se llama a ningún servicio externo. Ni a Cl@ve, ni a la Plataforma de
 * Intermediación, ni al servicio de notificación. Una petición del ciudadano no puede
 * quedar colgada porque un servicio ajeno tarde ocho segundos, y una transacción abierta
 * mientras se espera una respuesta de red es la mejor forma de tumbar una base de datos.
 * Todo eso lo hace el trabajador de la bandeja de salida, después.
 */
final readonly class SubmitApplication
{
    public function __construct(
        private ConnectionInterface $db,
        private AuditRecorder $audit,
        private OutboxRecorder $outbox,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $householdMembers  Miembros a congelar.
     *
     * @throws InvalidTransition si la solicitud no está en un estado desde el que pueda presentarse.
     */
    public function __invoke(string $applicationId, array $householdMembers, AuditActor $actor): Application
    {
        return $this->db->transaction(function () use ($applicationId, $householdMembers, $actor): Application {
            /** @var Application $application */
            $application = Application::query()
                ->whereKey($applicationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $application->status->canTransitionTo(ApplicationStatus::Submitted)) {
                throw new InvalidTransition($application->status, ApplicationStatus::Submitted);
            }

            $previousStatus = $application->status;
            $submittedAt = now();

            $application->forceFill([
                'status' => ApplicationStatus::Submitted,
                'submitted_at' => $submittedAt,
                'household_snapshot_members' => $householdMembers,
                'household_snapshot_member_count' => count($householdMembers),
            ])->save();

            $this->db->table('application_transitions')->insert([
                'id' => (string) Str::uuid7(),
                'application_id' => $application->id,
                'from_status' => $previousStatus->value,
                'to_status' => ApplicationStatus::Submitted->value,
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'reason' => null,
                'occurred_at' => $submittedAt,
            ]);

            $this->audit->record(
                action: 'application.submitted',
                entityType: 'application',
                entityId: $application->id,
                actor: $actor,
                metadata: [
                    'from' => $previousStatus->value,
                    'to' => ApplicationStatus::Submitted->value,
                    'call_id' => $application->call_id,
                    'member_count' => count($householdMembers),
                ],
            );

            $this->outbox->publish(
                aggregateType: 'application',
                aggregateId: $application->id,
                eventName: 'application.submitted',
                payload: [
                    'application_id' => $application->id,
                    'call_id' => $application->call_id,
                    'submitted_at' => $submittedAt->toIso8601String(),
                ],
            );

            return $application;
        });
    }
}
