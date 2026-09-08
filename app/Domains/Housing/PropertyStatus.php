<?php

declare(strict_types=1);

namespace App\Domains\Housing;

/**
 * Estado administrativo de una vivienda. Coincide exactamente con la
 * restricción CHECK `properties_status_check` de la migración.
 */
enum PropertyStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Awarded = 'awarded';
    case Leased = 'leased';
    case Unavailable = 'unavailable';
}
