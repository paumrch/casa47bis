<?php

declare(strict_types=1);

namespace App\Domains\Eligibility\Values;

/**
 * Resultado de un criterio concreto.
 *
 * El campo `explanation` no es un lujo de interfaz: una resolución administrativa que
 * excluye a alguien debe estar motivada, y el ciudadano tiene derecho a saber qué dato
 * concreto se usó y con qué umbral se comparó. Si el sistema no puede generar esa frase,
 * el organismo tendrá que escribirla a mano en cada expediente.
 */
final readonly class CriterionResult
{
    public function __construct(
        public string $code,
        public bool $passed,
        public string $explanation,
        /** @var array<string, scalar|null> Datos usados, para poder reconstruir la decisión años después. */
        public array $evidence = [],
    ) {}

    /** @param array<string, scalar|null> $evidence */
    public static function pass(string $code, string $explanation, array $evidence = []): self
    {
        return new self($code, true, $explanation, $evidence);
    }

    /** @param array<string, scalar|null> $evidence */
    public static function fail(string $code, string $explanation, array $evidence = []): self
    {
        return new self($code, false, $explanation, $evidence);
    }
}
