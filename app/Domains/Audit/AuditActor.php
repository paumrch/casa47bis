<?php

declare(strict_types=1);

namespace App\Domains\Audit;

/**
 * Quién realiza una acción.
 *
 * Puede no haber nadie: hay acciones que ejecuta el propio sistema (una caducidad por
 * plazo vencido, un trabajo programado). Esos casos se registran con `system()` y no
 * con un usuario ficticio, porque atribuir a una persona algo que hizo un `cron` es
 * falsear la traza.
 */
final readonly class AuditActor
{
    private function __construct(
        public ?string $type,
        public ?string $id,
        public ?string $ipAddress,
    ) {}

    public static function citizen(string $accountId, ?string $ipAddress = null): self
    {
        return new self('account', $accountId, $ipAddress);
    }

    public static function staff(string $staffUserId, ?string $ipAddress = null): self
    {
        return new self('staff_user', $staffUserId, $ipAddress);
    }

    /** Acción ejecutada por el sistema sin intervención humana. */
    public static function system(): self
    {
        return new self(null, null, null);
    }
}
