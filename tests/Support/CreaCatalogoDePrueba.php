<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domains\Calls\CallStatus;
use App\Domains\Calls\Models\Call;
use App\Domains\Housing\Models\Development;
use App\Domains\Housing\Models\Property;

/**
 * Catálogo mínimo para las pruebas del portal.
 *
 * No se usa el sembrado de demostración: genera 250 viviendas y, multiplicado por
 * cuarenta y tantas pruebas, convertía la suite en un minuto y medio de espera. Una
 * suite lenta se acaba ejecutando menos, y una suite que se ejecuta menos no protege
 * nada.
 *
 * Aquí se crea lo justo para que haya paginación (más de doce resultados), variedad de
 * dormitorios y de accesibilidad, y una convocatoria abierta.
 */
trait CreaCatalogoDePrueba
{
    protected function crearCatalogoDePrueba(): void
    {
        $developments = Development::factory()->count(3)->create();

        foreach ($developments as $index => $development) {
            Property::factory()
                ->count(10)
                ->available()
                ->for($development)
                ->sequence(
                    ['bedrooms' => 1, 'accessible' => false],
                    ['bedrooms' => 2, 'accessible' => false],
                    ['bedrooms' => 3, 'accessible' => true],
                    ['bedrooms' => 4, 'accessible' => false],
                    ['bedrooms' => 2, 'accessible' => true],
                )
                ->create(['municipality' => ['Valencia', 'Sevilla', 'Bilbao'][$index]]);
        }

        Call::factory()->create([
            'status' => CallStatus::Open,
            'opens_at' => now()->subWeek(),
            'closes_at' => now()->addWeeks(3),
        ]);

        Call::factory()->create([
            'status' => CallStatus::Closed,
            'opens_at' => now()->subMonths(6),
            'closes_at' => now()->subMonths(5),
        ]);
    }
}
