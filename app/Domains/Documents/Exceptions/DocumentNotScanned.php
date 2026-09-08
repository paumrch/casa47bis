<?php

declare(strict_types=1);

namespace App\Domains\Documents\Exceptions;

use App\Domains\Documents\ScanStatus;
use DomainException;

/**
 * Se intentó dar por válido o descargar un documento que no está en estado `Clean`.
 *
 * Cubre tanto el documento todavía `Pending` —el análisis no ha terminado— como el
 * `Infected` o el que terminó en `Error`: en los tres casos la respuesta correcta es la
 * misma, no servir el binario, y distinguirlos en el mensaje ayuda a quien opera el
 * sistema sin cambiar el comportamiento.
 */
final class DocumentNotScanned extends DomainException
{
    public function __construct(public readonly ScanStatus $status)
    {
        parent::__construct(match ($status) {
            ScanStatus::Pending => 'El documento todavía no ha sido analizado. No puede descargarse hasta que el análisis termine.',
            ScanStatus::Infected => 'El documento ha sido marcado como infectado. No se sirve nunca.',
            ScanStatus::Error => 'El análisis del documento no pudo completarse. No puede descargarse hasta reintentarlo.',
            ScanStatus::Clean => 'El documento está limpio; esta excepción no debería lanzarse en este estado.',
        });
    }
}
