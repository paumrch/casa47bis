<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Calls\CallStatus;
use App\Domains\Calls\Models\Call;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Call>
 */
class CallFactory extends Factory
{
    protected $model = Call::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $opensAt = $this->faker->dateTimeBetween('-2 months', '-1 week');
        $closesAt = (clone $opensAt)->modify('+2 months');

        return [
            'reference_code' => 'CONV-'.$this->faker->unique()->numerify('####/##'),
            'name' => 'Convocatoria de vivienda de alquiler asequible '.$this->faker->year(),
            'scope_description' => 'Adjudicación de viviendas del parque público destinadas a alquiler asequible.',
            'eligibility_rule_version' => '2026.1',
            'scoring_rule_version' => '2026.1',
            'quotas' => [
                'general' => 70,
                'familia_numerosa' => 15,
                'movilidad_reducida' => 10,
                'menores_35' => 5,
            ],
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'status' => CallStatus::Draft,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'opens_at' => now()->subWeek(),
            'closes_at' => now()->addMonth(),
            'status' => CallStatus::Open,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'opens_at' => now()->subMonths(3),
            'closes_at' => now()->subMonth(),
            'status' => CallStatus::Closed,
        ]);
    }
}
