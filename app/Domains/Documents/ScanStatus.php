<?php

declare(strict_types=1);

namespace App\Domains\Documents;

/**
 * Estado del análisis antivirus de un documento. Coincide exactamente con la
 * restricción CHECK `documents_scan_status_check` de la migración.
 *
 * Un documento sólo puede considerarse válido y descargable en estado `Clean`. Todos
 * los demás estados -incluido `Pending`, el estado inicial de todo documento recién
 * subido- bloquean la descarga: el binario existe en almacenamiento, pero nadie puede
 * darlo por bueno hasta que el análisis termine.
 */
enum ScanStatus: string
{
    /** Recién subido, a la espera de que el análisis lo procese. */
    case Pending = 'pending';

    /** Analizado sin hallazgos. Único estado que permite la descarga. */
    case Clean = 'clean';

    /** Analizado con hallazgo de malware. No se sirve jamás. */
    case Infected = 'infected';

    /** El análisis no pudo completarse. Se trata como no válido hasta reintentarlo. */
    case Error = 'error';

    /** Si un documento en este estado puede descargarse o darse por válido. */
    public function isDownloadable(): bool
    {
        return $this === self::Clean;
    }
}
