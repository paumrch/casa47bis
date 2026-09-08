<?php

declare(strict_types=1);

namespace App\Domains\Housing;

/**
 * Estado de obra de una promoción. Sin restricción CHECK en base de datos
 * (la migración lo deja como texto libre a propósito); estos son los
 * valores documentados en `create_developments_table`.
 */
enum ConstructionStatus: string
{
    case Planned = 'planned';
    case UnderConstruction = 'under_construction';
    case Finished = 'finished';
}
