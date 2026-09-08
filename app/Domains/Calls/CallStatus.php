<?php

declare(strict_types=1);

namespace App\Domains\Calls;

/**
 * Estado administrativo de una convocatoria. Coincide exactamente con la
 * restricción CHECK `calls_status_check` de la migración.
 */
enum CallStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';
}
