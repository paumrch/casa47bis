<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Eligibility;

use App\Domains\Eligibility\Values\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Aritmética de importes.
 *
 * Se prueba con esta insistencia porque un redondeo mal hecho en el cálculo de
 * 7,5 × IPREM desplaza el umbral de admisión y excluye o admite a quien no debe.
 */
final class MoneyTest extends TestCase
{
    /** @return array<string, array{string, int}> */
    public static function importes(): array
    {
        return [
            'entero' => ['8400', 840000],
            'con coma decimal' => ['16799,99', 1679999],
            'con punto decimal' => ['16799.99', 1679999],
            'un solo decimal' => ['10.5', 1050],
            'cero' => ['0', 0],
            'negativo' => ['-25,50', -2550],
        ];
    }

    #[Test]
    #[DataProvider('importes')]
    public function se_construye_desde_texto_sin_perder_centimos(string $input, int $cents): void
    {
        $this->assertSame($cents, Money::fromEuros($input)->cents);
    }

    #[Test]
    public function rechaza_importes_mal_formados(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::fromEuros('8.400,00');
    }

    #[Test]
    public function el_umbral_de_siete_y_medio_ipremes_es_exacto(): void
    {
        $this->assertSame(
            6_300_000,
            Money::fromEuros(8400)->times(7.5)->cents,
            '7,5 × 8.400 € debe dar exactamente 63.000,00 €.',
        );
    }

    #[Test]
    public function formatea_en_convencion_espanola(): void
    {
        $this->assertSame('63.000,00 €', (string) Money::fromEuros(63000));
        $this->assertSame('16.799,99', Money::fromEuros('16799,99')->toEuros());
    }

    #[Test]
    public function las_comparaciones_son_estrictas_en_el_umbral(): void
    {
        $limit = Money::fromEuros('16800');

        $this->assertTrue(Money::fromEuros('16799,99')->isLessThan($limit));
        $this->assertFalse($limit->isLessThan($limit));
        $this->assertFalse($limit->isGreaterThan($limit));
    }
}
