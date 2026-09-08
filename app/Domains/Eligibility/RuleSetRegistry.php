<?php

declare(strict_types=1);

namespace App\Domains\Eligibility;

use App\Domains\Eligibility\Rules\RuleSet;
use App\Domains\Eligibility\Rules\RuleSet2026;
use RuntimeException;

/**
 * Localiza el conjunto de reglas aplicable.
 *
 * Dos usos distintos y ambos necesarios:
 *
 *  - `current()` para evaluar una solicitud nueva.
 *  - `version()` para RE-evaluar una solicitud antigua con sus reglas originales,
 *    que es lo que hace falta cuando alguien recurre.
 *
 * Las versiones no se retiran nunca. Si dentro de ocho años este registro tiene ocho
 * entradas, estará bien.
 */
final class RuleSetRegistry
{
    /** @var array<string, RuleSet> */
    private array $sets;

    public function __construct()
    {
        $this->sets = [];

        foreach ([new RuleSet2026] as $set) {
            $this->sets[$set->version()] = $set;
        }
    }

    public function version(string $version): RuleSet
    {
        return $this->sets[$version]
            ?? throw new RuntimeException(
                "No existe el conjunto de reglas de elegibilidad «{$version}». ".
                'Las versiones históricas no deben eliminarse: son necesarias para resolver recursos.'
            );
    }

    /** Conjunto vigente en la fecha dada (por defecto, hoy). */
    public function current(?\DateTimeImmutable $at = null): RuleSet
    {
        $at ??= new \DateTimeImmutable;
        $applicable = null;

        foreach ($this->sets as $set) {
            if ($set->effectiveFrom() <= $at
                && ($applicable === null || $set->effectiveFrom() > $applicable->effectiveFrom())) {
                $applicable = $set;
            }
        }

        return $applicable ?? throw new RuntimeException(
            'No hay ningún conjunto de reglas de elegibilidad vigente en '.$at->format('Y-m-d').'.'
        );
    }

    /** @return list<string> */
    public function availableVersions(): array
    {
        return array_keys($this->sets);
    }
}
