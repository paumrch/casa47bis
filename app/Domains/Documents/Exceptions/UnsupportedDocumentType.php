<?php

declare(strict_types=1);

namespace App\Domains\Documents\Exceptions;

use DomainException;

/**
 * El contenido real del fichero no está en la lista blanca de tipos admitidos.
 *
 * Se lanza a partir del tipo MIME detectado por `finfo` sobre el contenido, nunca a
 * partir de la extensión del nombre de fichero ni de la cabecera `Content-Type` que
 * envía el navegador: ambas las controla quien sube el fichero, y un ejecutable
 * renombrado a `nomina.pdf` con cabecera `application/pdf` pasaría cualquier
 * comprobación que confiara en ellas.
 */
final class UnsupportedDocumentType extends DomainException
{
    public function __construct(public readonly string $detectedMimeType)
    {
        parent::__construct(
            "El tipo de fichero detectado «{$detectedMimeType}» no está admitido. ".
            'Sólo se aceptan PDF, JPEG, PNG y TIFF, determinado por el contenido real del fichero.'
        );
    }
}
