<?php

declare(strict_types=1);

namespace App\Domains\Documents\Exceptions;

use DomainException;

/**
 * Quien pide la URL de descarga no tiene derecho a este documento.
 *
 * No forma parte de la lista de excepciones pedida explícitamente para el módulo, pero
 * la emisión de una URL firmada (§5) exige comprobar el derecho de acceso antes de
 * emitirla, y esa comprobación necesita una forma de fallar que no sea un 500 genérico.
 */
final class DocumentAccessDenied extends DomainException
{
    public function __construct(public readonly string $documentId)
    {
        parent::__construct(
            "No tiene derecho de acceso al documento «{$documentId}»."
        );
    }
}
