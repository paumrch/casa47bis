<?php

declare(strict_types=1);

namespace App\Domains\Eligibility\Values;

use InvalidArgumentException;

/**
 * Importe en euros, representado en céntimos.
 *
 * No se usan float en ningún punto del dominio económico. Un error de redondeo aquí
 * no es un detalle: decide si una familia entra o no en una convocatoria de vivienda.
 */
final readonly class Money implements \Stringable
{
    private function __construct(public int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromEuros(int|string $euros): self
    {
        if (is_int($euros)) {
            return new self($euros * 100);
        }

        if (! preg_match('/^-?\d+(?:[.,]\d{1,2})?$/', $euros)) {
            throw new InvalidArgumentException("Importe no válido: «{$euros}».");
        }

        $normalised = str_replace(',', '.', $euros);
        [$whole, $fraction] = array_pad(explode('.', $normalised), 2, '0');
        $sign = str_starts_with($whole, '-') ? -1 : 1;

        return new self($sign * (abs((int) $whole) * 100 + (int) str_pad($fraction, 2, '0')));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    /** Multiplica por un factor decimal redondeando al céntimo (redondeo bancario). */
    public function times(float $factor): self
    {
        return new self((int) round($this->cents * $factor, 0, PHP_ROUND_HALF_EVEN));
    }

    public function isLessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function toEuros(): string
    {
        return number_format($this->cents / 100, 2, ',', '.');
    }

    public function __toString(): string
    {
        return $this->toEuros().' €';
    }
}
