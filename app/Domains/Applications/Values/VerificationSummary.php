<?php

declare(strict_types=1);

namespace App\Domains\Applications\Values;

use App\Domains\Applications\ApplicationStatus;
use App\Domains\Eligibility\Values\EligibilityResult;

/**
 * Resultado de comprobar los requisitos de una solicitud.
 *
 * Tres desenlaces posibles, no dos: admitida, excluida, o **a la espera de
 * documentación** porque no se pudo comprobar algo automáticamente. El tercero es el que
 * se olvida al diseñar y el que más veces ocurre en producción.
 */
final readonly class VerificationSummary
{
    /**
     * @param  array<string, string>  $outcomes  Comprobación => resultado (confirmed/refuted/unavailable).
     * @param  list<string>  $documentsRequired  Comprobaciones que habrá que acreditar con documentos.
     */
    public function __construct(
        public ApplicationStatus $status,
        public array $outcomes,
        public array $documentsRequired,
        public ?EligibilityResult $eligibility,
    ) {}

    public function needsDocuments(): bool
    {
        return $this->documentsRequired !== [];
    }
}
