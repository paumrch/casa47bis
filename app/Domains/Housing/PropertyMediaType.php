<?php

declare(strict_types=1);

namespace App\Domains\Housing;

/**
 * Tipo de material gráfico asociado a una vivienda. Coincide exactamente
 * con la restricción CHECK `property_media_media_type_check`.
 */
enum PropertyMediaType: string
{
    case Photo = 'photo';
    case FloorPlan = 'floor_plan';
    case VirtualTour = 'virtual_tour';
}
