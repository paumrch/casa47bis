<?php

declare(strict_types=1);

namespace App\Integrations\Contracts\Values;

/**
 * Resultado de una verificación externa, con el momento en que se obtuvo.
 *
 * La fecha importa: un dato de la AEAT es cierto cuando se consultó, no para siempre.
 */
final readonly class VerificationOutcome
{
    /** @param array<string, scalar|null> $data */
    private function __construct(
        public VerificationStatus $status,
        public array $data,
        public ?string $reason,
        public \DateTimeImmutable $obtainedAt,
    ) {}

    /** @param array<string, scalar|null> $data */
    public static function confirmed(array $data): self
    {
        return new self(VerificationStatus::Confirmed, $data, null, new \DateTimeImmutable);
    }

    /** @param array<string, scalar|null> $data */
    public static function refuted(string $reason, array $data = []): self
    {
        return new self(VerificationStatus::Refuted, $data, $reason, new \DateTimeImmutable);
    }

    /** El servicio no respondió. El expediente sigue por la vía documental. */
    public static function unavailable(string $reason): self
    {
        return new self(VerificationStatus::Unavailable, [], $reason, new \DateTimeImmutable);
    }

    public function requiresManualFallback(): bool
    {
        return $this->status === VerificationStatus::Unavailable;
    }
}
