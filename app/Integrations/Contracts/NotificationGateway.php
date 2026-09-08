<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

/**
 * Puerto de comunicación con el ciudadano.
 *
 * DISTINCIÓN CRÍTICA que este puerto obliga a hacer explícita:
 *
 *  - Una NOTIFICACIÓN FEHACIENTE produce efectos jurídicos y hace correr plazos.
 *    Va por DEHú/Notifica, tiene acuse y su fecha es oponible.
 *  - Un AVISO es cortesía. Un correo electrónico. No hace correr ningún plazo.
 *
 * Confundirlas es uno de los defectos más graves que puede tener un sistema
 * administrativo, porque genera indefensión. Por eso son dos métodos distintos y no
 * un parámetro booleano que alguien pueda pasar mal.
 */
interface NotificationGateway
{
    /** Notificación con efectos jurídicos. Devuelve el identificador de acuse. */
    /** @param list<string> $attachments Claves de almacenamiento de los documentos a adjuntar. */
    public function notifyFormally(string $recipientDocumentNumber, string $subject, string $body, array $attachments = []): string;

    /** Aviso informativo. Sin efectos de plazo. */
    public function sendAdvisory(string $recipientEmail, string $subject, string $body): void;
}
