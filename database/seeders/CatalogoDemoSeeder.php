<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Calls\Models\Call;
use App\Domains\Housing\Models\Development;
use App\Domains\Housing\Models\Property;
use App\Domains\Housing\PropertyStatus;
use Database\Factories\PropertyMediaFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo verosímil para el demostrador público: promociones, viviendas,
 * material gráfico y un par de convocatorias con viviendas asociadas.
 *
 * Idempotente: vacía primero las tablas que gestiona, en orden de
 * dependencia, y las vuelve a poblar. No toca `applications`, `awards` ni
 * ninguna otra tabla ajena a este dominio.
 */
class CatalogoDemoSeeder extends Seeder
{
    /** Viviendas por promoción. Suman 250. */
    private const VIVIENDAS_POR_PROMOCION = [40, 35, 35, 30, 30, 25, 30, 25];

    public function run(): void
    {
        DB::table('call_property')->delete();
        DB::table('property_media')->delete();
        Property::query()->forceDelete();
        Call::query()->forceDelete();
        Development::query()->delete();

        $properties = collect();

        foreach (self::VIVIENDAS_POR_PROMOCION as $numeroViviendas) {
            $development = Development::factory()->create();

            $nuevas = Property::factory()
                ->count($numeroViviendas)
                ->for($development)
                ->state(new Sequence(
                    ['status' => PropertyStatus::Available],
                    ['status' => PropertyStatus::Available],
                    ['status' => PropertyStatus::Available],
                    ['status' => PropertyStatus::Reserved],
                    ['status' => PropertyStatus::Awarded],
                    ['status' => PropertyStatus::Leased],
                ))
                ->create();

            $nuevas->each(function (Property $property): void {
                PropertyMediaFactory::new()
                    ->count(random_int(2, 5))
                    ->for($property)
                    ->create();
            });

            $properties = $properties->merge($nuevas);
        }

        $convocatoriaAbierta = Call::factory()->open()->create([
            'reference_code' => 'CONV-2026/01',
            'name' => 'Convocatoria de vivienda de alquiler asequible 2026',
        ]);

        $convocatoriaCerrada = Call::factory()->closed()->create([
            'reference_code' => 'CONV-2025/02',
            'name' => 'Convocatoria de vivienda de alquiler asequible 2025',
        ]);

        $disponibles = $properties->where('status', PropertyStatus::Available);

        $convocatoriaAbierta->properties()->attach(
            $disponibles->random(min(80, $disponibles->count()))->pluck('id'),
        );

        $convocatoriaCerrada->properties()->attach(
            $properties->random(min(60, $properties->count()))->pluck('id'),
        );
    }
}
