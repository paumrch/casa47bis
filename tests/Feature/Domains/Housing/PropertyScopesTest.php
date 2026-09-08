<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Housing;

use App\Domains\Housing\Models\Property;
use App\Domains\Housing\PropertyStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ámbitos de consulta del catálogo. Corre contra PostgreSQL de verdad
 * porque la búsqueda textual depende de `pg_trgm`, una extensión que
 * SQLite no tiene.
 */
final class PropertyScopesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function filtra_por_municipio(): void
    {
        Property::factory()->create(['municipality' => 'Valladolid']);
        Property::factory()->create(['municipality' => 'Burgos']);

        $resultado = Property::query()->inMunicipality('Valladolid')->get();

        $this->assertCount(1, $resultado);
        $this->assertSame('Valladolid', $resultado->firstOrFail()->municipality);
    }

    #[Test]
    public function filtra_por_numero_de_dormitorios(): void
    {
        Property::factory()->create(['bedrooms' => 2]);
        Property::factory()->create(['bedrooms' => 3]);

        $resultado = Property::query()->withBedrooms(3)->get();

        $this->assertCount(1, $resultado);
        $this->assertSame(3, $resultado->firstOrFail()->bedrooms);
    }

    #[Test]
    public function filtra_por_renta_maxima(): void
    {
        Property::factory()->create(['monthly_rent' => 400]);
        Property::factory()->create(['monthly_rent' => 700]);

        $resultado = Property::query()->withMaxRent(500)->get();

        $this->assertCount(1, $resultado);
        $this->assertSame('400.00', $resultado->firstOrFail()->monthly_rent);
    }

    #[Test]
    public function filtra_solo_accesibles(): void
    {
        Property::factory()->create(['accessible' => true]);
        Property::factory()->create(['accessible' => false]);

        $resultado = Property::query()->accessible()->get();

        $this->assertCount(1, $resultado);
        $this->assertTrue($resultado->firstOrFail()->accessible);
    }

    #[Test]
    public function filtra_solo_disponibles(): void
    {
        Property::factory()->create(['status' => PropertyStatus::Available]);
        Property::factory()->create(['status' => PropertyStatus::Leased]);

        $resultado = Property::query()->available()->get();

        $this->assertCount(1, $resultado);
        $this->assertSame(PropertyStatus::Available, $resultado->firstOrFail()->status);
    }

    #[Test]
    public function los_filtros_combinados_se_comportan_como_una_conjuncion(): void
    {
        // La única vivienda que cumple TODOS los criterios a la vez.
        Property::factory()->create([
            'municipality' => 'Valladolid',
            'bedrooms' => 2,
            'monthly_rent' => 450,
            'accessible' => true,
            'status' => PropertyStatus::Available,
        ]);

        // Cada una de estas incumple un único criterio.
        Property::factory()->create([
            'municipality' => 'Burgos', // municipio distinto
            'bedrooms' => 2,
            'monthly_rent' => 450,
            'accessible' => true,
            'status' => PropertyStatus::Available,
        ]);
        Property::factory()->create([
            'municipality' => 'Valladolid',
            'bedrooms' => 3, // dormitorios distintos
            'monthly_rent' => 450,
            'accessible' => true,
            'status' => PropertyStatus::Available,
        ]);
        Property::factory()->create([
            'municipality' => 'Valladolid',
            'bedrooms' => 2,
            'monthly_rent' => 900, // renta por encima del máximo
            'accessible' => true,
            'status' => PropertyStatus::Available,
        ]);
        Property::factory()->create([
            'municipality' => 'Valladolid',
            'bedrooms' => 2,
            'monthly_rent' => 450,
            'accessible' => false, // no accesible
            'status' => PropertyStatus::Available,
        ]);
        Property::factory()->create([
            'municipality' => 'Valladolid',
            'bedrooms' => 2,
            'monthly_rent' => 450,
            'accessible' => true,
            'status' => PropertyStatus::Leased, // no disponible
        ]);

        $resultado = Property::query()
            ->inMunicipality('Valladolid')
            ->withBedrooms(2)
            ->withMaxRent(500)
            ->accessible()
            ->available()
            ->get();

        $this->assertCount(1, $resultado);
    }

    #[Test]
    public function la_busqueda_tolera_una_errata_en_el_municipio(): void
    {
        Property::factory()->create(['municipality' => 'Valladolid']);
        Property::factory()->create(['municipality' => 'Ávila']);

        $resultado = Property::query()->search('Valladolit')->get();

        $this->assertCount(1, $resultado);
        $this->assertSame('Valladolid', $resultado->firstOrFail()->municipality);
    }

    #[Test]
    public function la_busqueda_encuentra_por_referencia_catastral_exacta(): void
    {
        $property = Property::factory()->create(['reference_code' => '9872035VK4897S0001WX']);
        Property::factory()->create(['reference_code' => '1234567AB1234C0001YZ']);

        $resultado = Property::query()->search('9872035VK4897S0001WX')->get();

        $this->assertCount(1, $resultado);
        $this->assertSame($property->id, $resultado->firstOrFail()->id);
    }
}
