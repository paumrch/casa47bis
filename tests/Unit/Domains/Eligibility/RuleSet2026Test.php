<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Eligibility;

use App\Domains\Eligibility\Rules\RuleSet2026;
use App\Domains\Eligibility\Values\ApplicantProfile;
use App\Domains\Eligibility\Values\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Criterios de acceso de la convocatoria de 2026.
 *
 * Aquí es donde un error deniega un derecho a una persona real, así que la densidad
 * de casos límite es máxima y deliberada. Los umbrales se prueban en el céntimo
 * exacto, por encima y por debajo.
 */
final class RuleSet2026Test extends TestCase
{
    private RuleSet2026 $rules;

    protected function setUp(): void
    {
        $this->rules = new RuleSet2026;
    }

    private function profile(
        string $income = '30000',
        bool $residence = true,
        bool $owns = false,
        bool $unusable = false,
        bool $aeat = true,
        bool $ss = true,
        int $size = 2,
    ): ApplicantProfile {
        return new ApplicantProfile(
            householdSize: $size,
            annualNetIncome: Money::fromEuros($income),
            hasLegalResidence: $residence,
            ownsProperty: $owns,
            ownedPropertyIsUnusable: $unusable,
            isCurrentWithTaxAuthority: $aeat,
            isCurrentWithSocialSecurity: $ss,
        );
    }

    #[Test]
    public function una_unidad_que_cumple_todo_es_admitida(): void
    {
        $result = $this->rules->evaluate($this->profile());

        $this->assertTrue($result->isEligible());
        $this->assertSame([], $result->failures());
        $this->assertSame('2026.1', $result->ruleSetVersion);
    }

    /**
     * IPREM anual de referencia: 8.400 €. Intervalo: [16.800 €, 63.000 €].
     *
     * @return array<string, array{string, bool}>
     */
    public static function umbralesDeIngresos(): array
    {
        return [
            'un céntimo por debajo del mínimo' => ['16799.99', false],
            'exactamente el mínimo, 2 IPREM' => ['16800.00', true],
            'un céntimo por encima del mínimo' => ['16800.01', true],
            'en mitad del intervalo' => ['40000.00', true],
            'un céntimo por debajo del máximo' => ['62999.99', true],
            'exactamente el máximo, 7,5 IPREM' => ['63000.00', true],
            'un céntimo por encima del máximo' => ['63000.01', false],
            'ingresos nulos' => ['0', false],
            'ingresos muy altos' => ['250000', false],
        ];
    }

    #[Test]
    #[DataProvider('umbralesDeIngresos')]
    public function el_intervalo_de_ingresos_es_cerrado_en_ambos_extremos(string $income, bool $expected): void
    {
        $result = $this->rules->evaluate($this->profile(income: $income));

        $this->assertSame(
            $expected,
            $result->isEligible(),
            "Con ingresos de {$income} € se esperaba ".($expected ? 'admisión' : 'exclusión').'.',
        );
    }

    #[Test]
    public function los_ingresos_por_debajo_del_minimo_se_explican_al_ciudadano(): void
    {
        $result = $this->rules->evaluate($this->profile(income: '10000'));

        $this->assertFalse($result->isEligible());
        $this->assertStringContainsString('no alcanzan el mínimo', $result->motivation());
        $this->assertStringContainsString('16.800,00 €', $result->motivation());
    }

    #[Test]
    public function la_vivienda_en_propiedad_excluye_salvo_que_no_sea_utilizable(): void
    {
        $this->assertFalse($this->rules->evaluate($this->profile(owns: true))->isEligible());
        $this->assertTrue($this->rules->evaluate($this->profile(owns: true, unusable: true))->isEligible());
    }

    #[Test]
    public function se_informa_de_todo_s_los_incumplimientos_a_la_vez(): void
    {
        $result = $this->rules->evaluate($this->profile(
            income: '90000',
            residence: false,
            owns: true,
            aeat: false,
            ss: false,
        ));

        $this->assertFalse($result->isEligible());
        $this->assertCount(
            5,
            $result->failures(),
            'Cortocircuitar la evaluación obligaría al ciudadano a subsanar de uno en uno.',
        );
    }

    #[Test]
    public function la_evaluacion_siempre_recorre_los_cinco_criterios(): void
    {
        $result = $this->rules->evaluate($this->profile());

        $this->assertEqualsCanonicalizing(
            ['income', 'residence', 'property_ownership', 'tax_compliance', 'social_security_compliance'],
            array_map(static fn ($c) => $c->code, $result->criteria),
        );
    }

    #[Test]
    public function la_traza_de_auditoria_permite_reconstruir_la_decision(): void
    {
        $trail = $this->rules->evaluate($this->profile(income: '16799.99'))->toAuditTrail();

        $this->assertSame('2026.1', $trail['rule_set_version']);
        $this->assertFalse($trail['eligible']);

        $income = current(array_filter($trail['criteria'], static fn ($c) => $c['code'] === 'income'));

        $this->assertSame('16.799,99', $income['evidence']['ingresos_netos_anuales']);
        $this->assertSame('8.400,00', $income['evidence']['iprem_anual']);
        $this->assertSame('16.800,00', $income['evidence']['limite_inferior']);
        $this->assertSame('63.000,00', $income['evidence']['limite_superior']);
    }

    #[Test]
    public function la_motivacion_de_una_admision_es_explicita(): void
    {
        $this->assertStringContainsString(
            'cumple todos los requisitos',
            $this->rules->evaluate($this->profile())->motivation(),
        );
    }
}
