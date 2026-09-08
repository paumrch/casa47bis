<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Housing\Models\Development;
use App\Domains\Housing\Models\Property;
use App\Domains\Housing\PropertyStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $localidad = $this->faker->randomElement(MunicipiosEspanoles::TODOS);
        $bedrooms = $this->faker->numberBetween(1, 4);

        return [
            'development_id' => Development::factory(),
            'reference_code' => $this->referenciaCatastral(),
            'bedrooms' => $bedrooms,
            'bathrooms' => $bedrooms >= 3 ? 2 : 1,
            'surface_m2' => $this->faker->randomFloat(2, 40, 110),
            'floor' => $this->faker->randomElement(['Bajo', '1º', '2º', '3º', '4º', '5º', 'Ático']),
            'has_elevator' => $this->faker->boolean(70),
            'accessible' => $this->faker->boolean(20),
            'has_garage' => $this->faker->boolean(40),
            'has_storage_room' => $this->faker->boolean(35),
            'monthly_rent' => $this->faker->randomFloat(2, 300, 750),
            'status' => PropertyStatus::Available,
            'address_line' => $this->faker->streetName().', '.$this->faker->buildingNumber(),
            'municipality' => $localidad['municipio'],
            'province' => $localidad['provincia'],
            'postal_code' => $this->faker->numerify('#####'),
            'latitude' => $this->faker->randomFloat(6, 36.0, 43.5),
            'longitude' => $this->faker->randomFloat(6, -9.0, 3.3),
        ];
    }

    /** Formato plausible de referencia catastral urbana (20 caracteres). */
    private function referenciaCatastral(): string
    {
        return $this->faker->numerify('#######').
            $this->faker->bothify('??').
            $this->faker->numerify('####').
            $this->faker->bothify('?').
            '0001'.
            $this->faker->bothify('??');
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PropertyStatus::Available,
        ]);
    }

    public function awarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PropertyStatus::Awarded,
        ]);
    }

    public function accessible(): static
    {
        return $this->state(fn (array $attributes): array => [
            'accessible' => true,
            'has_elevator' => true,
        ]);
    }
}
