<?php

declare(strict_types=1);

namespace App\Domains\Applications\Exceptions;

use App\Domains\Applications\ApplicationStatus;
use DomainException;

/**
 * Se intentó un cambio de estado que el procedimiento no contempla.
 *
 * Es un error de dominio, no de programación: puede originarse en una petición
 * concurrente legítima (dos gestores actuando a la vez sobre el mismo expediente),
 * así que la capa HTTP debe traducirlo a un 409 y no a un 500.
 */
final class InvalidTransition extends DomainException
{
    public function __construct(
        public readonly ApplicationStatus $from,
        public readonly ApplicationStatus $to,
    ) {
        parent::__construct(sprintf(
            'La solicitud no puede pasar de «%s» a «%s». Transiciones permitidas: %s.',
            $from->value,
            $to->value,
            $from->isFinal()
                ? 'ninguna, es un estado final'
                : implode(', ', array_map(static fn ($s) => $s->value, $from->allowedNext())),
        ));
    }
}
