<?php

declare(strict_types=1);

namespace App\Domains\Documents\Exceptions;

use DomainException;

/** El fichero supera el tamaño máximo admitido para un documento acreditativo. */
final class DocumentTooLarge extends DomainException
{
    public function __construct(
        public readonly int $sizeBytes,
        public readonly int $maxSizeBytes,
    ) {
        parent::__construct(sprintf(
            'El fichero ocupa %d bytes, que supera el máximo admitido de %d bytes.',
            $sizeBytes,
            $maxSizeBytes,
        ));
    }
}
