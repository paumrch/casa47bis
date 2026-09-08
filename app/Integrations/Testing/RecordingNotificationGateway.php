<?php

declare(strict_types=1);

namespace App\Integrations\Testing;

use App\Integrations\Contracts\NotificationGateway;

/**
 * Doble de notificación que registra lo enviado.
 *
 * Separa deliberadamente las fehacientes de los avisos, igual que el contrato, para que
 * una prueba pueda afirmar que un acto con efectos de plazo se notificó por la vía que
 * corresponde y no por correo electrónico.
 */
final class RecordingNotificationGateway implements NotificationGateway
{
    /** @var list<array{recipient: string, subject: string, body: string, attachments: list<string>}> */
    public array $formal = [];

    /** @var list<array{recipient: string, subject: string, body: string}> */
    public array $advisory = [];

    public bool $shouldFail = false;

    /** @param list<string> $attachments */
    public function notifyFormally(string $recipientDocumentNumber, string $subject, string $body, array $attachments = []): string
    {
        if ($this->shouldFail) {
            throw new \RuntimeException('El servicio de notificación no responde.');
        }

        $this->formal[] = [
            'recipient' => $recipientDocumentNumber,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachments,
        ];

        return 'ACUSE-'.count($this->formal);
    }

    public function sendAdvisory(string $recipientEmail, string $subject, string $body): void
    {
        $this->advisory[] = [
            'recipient' => $recipientEmail,
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
