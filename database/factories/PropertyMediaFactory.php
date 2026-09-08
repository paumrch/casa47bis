<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Housing\Models\Property;
use App\Domains\Housing\Models\PropertyMedia;
use App\Domains\Housing\PropertyMediaType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyMedia>
 */
class PropertyMediaFactory extends Factory
{
    protected $model = PropertyMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mediaType = $this->faker->randomElement(PropertyMediaType::cases());

        return [
            'property_id' => Property::factory(),
            'media_type' => $mediaType,
            'storage_key' => sprintf(
                'viviendas/%s/%s.jpg',
                $this->faker->uuid(),
                $mediaType->value,
            ),
            'position' => $this->faker->numberBetween(0, 9),
        ];
    }
}
