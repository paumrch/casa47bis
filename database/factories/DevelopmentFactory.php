<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Housing\ConstructionStatus;
use App\Domains\Housing\Models\Development;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Development>
 */
class DevelopmentFactory extends Factory
{
    protected $model = Development::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $localidad = $this->faker->randomElement(MunicipiosEspanoles::TODOS);

        return [
            'name' => 'Residencial '.$this->faker->randomElement([
                'Los Almendros', 'El Mirador', 'Las Encinas', 'San Isidro', 'La Alameda',
                'El Pinar', 'Los Rosales', 'Fuente Nueva', 'El Olivar', 'Las Acacias',
            ]),
            'developer_name' => $this->faker->randomElement([
                'EMVS', 'IVIMA', 'Sociedad Municipal de la Vivienda', 'AVRA',
                'VISESA', 'EPSA', 'Instituto de la Vivienda de Madrid',
            ]),
            'construction_status' => $this->faker->randomElement(ConstructionStatus::cases()),
            'address_line' => $this->faker->streetName().', '.$this->faker->buildingNumber(),
            'municipality' => $localidad['municipio'],
            'province' => $localidad['provincia'],
            'postal_code' => $this->faker->numerify('#####'),
            'latitude' => $this->faker->randomFloat(6, 36.0, 43.5),
            'longitude' => $this->faker->randomFloat(6, -9.0, 3.3),
        ];
    }
}
