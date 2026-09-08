<?php

declare(strict_types=1);

namespace App\Domains\Notifications;

use App\Domains\Shared\Outbox\OutboxEvent;
use App\Domains\Shared\Outbox\OutboxHandler;
use App\Integrations\Contracts\NotificationGateway;

/**
 * Comunica al ciudadano los cambios de estado de su solicitud.
 *
 * Consume la bandeja de salida, así que se ejecuta FUERA de la petición del ciudadano y
 * fuera de la transacción que cambió el estado. Si el servicio de notificación tarda diez
 * segundos o está caído, nadie se queda esperando y el reintento lo gestiona el
 * despachador.
 *
 * POR QUÉ VIVE EN EL DOMINIO Y NO ENTRE LOS ADAPTADORES
 *
 * Estuvo en `App\Integrations` hasta que la comprobación de fronteras la rechazó, y con
 * razón. Decidir qué actos se notifican de forma fehaciente no es fontanería de
 * integración: es conocimiento jurídico del procedimiento. Un adaptador se sustituye
 * cuando cambia el proveedor; esta decisión sólo cambia si cambia la norma.
 *
 * La clase usa el PUERTO de notificación, así que sigue sin saber si al otro lado hay
 * DEHú, un correo o un doble de pruebas.
 *
 * LA DISTINCIÓN QUE HACE ESTA CLASE, Y QUE ES LA IMPORTANTE
 *
 * Los actos que producen efectos jurídicos —la admisión, la exclusión, la adjudicación—
 * se notifican de forma FEHACIENTE, porque desde ese momento corren plazos para recurrir.
 * Lo demás son avisos de cortesía.
 *
 * Notificar por correo electrónico un acto que abre un plazo de recurso, o al revés,
 * saturar la vía fehaciente con avisos, son dos errores distintos y ambos serios. Por eso
 * la decisión se toma aquí, en un solo sitio, con el evento delante.
 */
final readonly class ApplicationStatusNotifier implements OutboxHandler
{
    public function __construct(private NotificationGateway $notifications) {}

    /** @return list<string> */
    public function subscribesTo(): array
    {
        return [
            'application.submitted',
            'application.documents_required',
            'application.eligible',
            'application.ineligible',
        ];
    }

    public function handle(OutboxEvent $event): void
    {
        $recipient = (string) ($event->payload['applicant_document_number'] ?? $event->aggregateId);

        match ($event->eventName) {
            // Acuse de recibo. No abre plazo: informa de que se recibió.
            'application.submitted' => $this->notifications->sendAdvisory(
                (string) ($event->payload['email'] ?? 'sin-correo@example.invalid'),
                'Hemos recibido tu solicitud',
                'Tu solicitud ha quedado registrada. Te avisaremos de cualquier novedad.',
            ),

            // Requerimiento de subsanación: abre plazo. Vía fehaciente.
            'application.documents_required' => $this->notifications->notifyFormally(
                $recipient,
                'Requerimiento de documentación',
                $this->documentsBody($event),
            ),

            // Resoluciones: abren plazo de recurso. Vía fehaciente.
            'application.eligible',
            'application.ineligible' => $this->notifications->notifyFormally(
                $recipient,
                'Resolución sobre tu solicitud',
                (string) ($event->payload['motivation'] ?? 'Se ha resuelto tu solicitud.'),
            ),

            default => null,
        };
    }

    private function documentsBody(OutboxEvent $event): string
    {
        /** @var list<string> $checks */
        $checks = is_array($event->payload['checks'] ?? null) ? $event->payload['checks'] : [];

        $etiquetas = [
            'income' => 'justificación de ingresos',
            'residence' => 'acreditación de residencia legal',
            'property_ownership' => 'acreditación de no ser titular de vivienda',
            'tax_compliance' => 'certificado de estar al corriente con la Agencia Tributaria',
            'social_security_compliance' => 'certificado de estar al corriente con la Seguridad Social',
        ];

        $lista = array_map(
            static fn (string $check): string => '- '.($etiquetas[$check] ?? $check),
            $checks,
        );

        return 'No ha sido posible comprobar de oficio los siguientes datos, por lo que se '
            ."te requiere que aportes la documentación correspondiente:\n\n"
            .implode("\n", $lista);
    }
}
