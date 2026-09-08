<?php

declare(strict_types=1);

namespace App\Integrations\Contracts\Values;

/**
 * Resultado de una verificación externa.
 *
 * Tres estados, no dos. `Unavailable` es el que suele olvidarse y el que más veces
 * ocurre en producción.
 */
enum VerificationStatus: string
{
    case Confirmed = 'confirmed';
    case Refuted = 'refuted';
    case Unavailable = 'unavailable';
}
