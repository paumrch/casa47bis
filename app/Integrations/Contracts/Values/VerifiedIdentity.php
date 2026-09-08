<?php

declare(strict_types=1);

namespace App\Integrations\Contracts\Values;

final readonly class VerifiedIdentity
{
    public function __construct(
        public string $documentNumber,
        public string $givenName,
        public string $surname,
        /** Nivel de aseguramiento eIDAS: low, substantial, high. */
        public string $levelOfAssurance,
        public \DateTimeImmutable $authenticatedAt,
    ) {}
}
