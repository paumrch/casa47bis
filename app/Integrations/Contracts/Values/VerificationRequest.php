<?php

declare(strict_types=1);

namespace App\Integrations\Contracts\Values;

/**
 * Petición de verificación.
 *
 * `purpose` y `consentReference` no son campos decorativos: la cesión de datos entre
 * administraciones exige una autorización ligada al procedimiento concreto, y hay que
 * poder demostrar años después con qué amparo se consultó el dato de una persona.
 */
final readonly class VerificationRequest
{
    public function __construct(
        public string $check,
        public string $subjectDocumentNumber,
        public string $purpose,
        public string $consentReference,
        public string $correlationId,
    ) {}
}
