<?php

declare(strict_types=1);

namespace App\Domains\Eligibility\Rules;

use App\Domains\Eligibility\Values\ApplicantProfile;
use App\Domains\Eligibility\Values\CriterionResult;
use App\Domains\Eligibility\Values\EligibilityResult;
use App\Domains\Eligibility\Values\Money;

/**
 * Criterios de acceso publicados para las convocatorias de 2026.
 *
 * FUENTE: requisitos publicados en el portal ciudadano de CASA 47. Se transcriben
 * aquí como reglas ejecutables. Si la norma dice otra cosa, manda la norma y esta
 * clase está mal: por eso cada criterio lleva su referencia.
 *
 * Los umbrales son constantes de ESTA versión. Cuando cambien, se crea RuleSet2027
 * y esta clase se queda intacta para siempre.
 */
final class RuleSet2026 implements RuleSet
{
    /**
     * IPREM anual, 14 pagas, vigente para el ejercicio de referencia.
     *
     * Es el parámetro del que cuelga todo el criterio económico. Está aquí, con nombre
     * y con fuente, en lugar de aparecer multiplicado dentro de una consulta SQL.
     */
    private const IPREM_ANUAL_EUROS = 8400;

    private const MIN_IPREM_MULTIPLIER = 2.0;

    private const MAX_IPREM_MULTIPLIER = 7.5;

    public function version(): string
    {
        return '2026.1';
    }

    public function effectiveFrom(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-01 00:00:00');
    }

    /**
     * @return list<array{code: string, title: string, detail: string}>
     */
    public function describe(): array
    {
        $iprem = Money::fromEuros(self::IPREM_ANUAL_EUROS);

        return [
            [
                'code' => 'income',
                'title' => 'Ingresos de la unidad de convivencia',
                'detail' => sprintf(
                    'Los ingresos netos anuales conjuntos deben situarse entre %s y %s, '.
                    'es decir, entre 2 y 7,5 veces el IPREM anual vigente (%s).',
                    $iprem->times(self::MIN_IPREM_MULTIPLIER),
                    $iprem->times(self::MAX_IPREM_MULTIPLIER),
                    $iprem,
                ),
            ],
            [
                'code' => 'residence',
                'title' => 'Nacionalidad o residencia legal',
                'detail' => 'Debe acreditarse nacionalidad española o residencia legal en España.',
            ],
            [
                'code' => 'property_ownership',
                'title' => 'No ser titular de vivienda',
                'detail' => 'Ningún miembro de la unidad de convivencia puede ser titular de una vivienda '.
                    'en propiedad. Se exceptúan los casos en que la vivienda no es utilizable como '.
                    'residencia y así se acredita.',
            ],
            [
                'code' => 'tax_compliance',
                'title' => 'Obligaciones tributarias',
                'detail' => 'Debe acreditarse estar al corriente de las obligaciones con la Agencia Tributaria.',
            ],
            [
                'code' => 'social_security_compliance',
                'title' => 'Obligaciones con la Seguridad Social',
                'detail' => 'Debe acreditarse estar al corriente de las obligaciones con la Seguridad Social.',
            ],
        ];
    }

    public function evaluate(ApplicantProfile $profile): EligibilityResult
    {
        return new EligibilityResult(
            ruleSetVersion: $this->version(),
            criteria: [
                $this->checkIncome($profile),
                $this->checkResidence($profile),
                $this->checkPropertyOwnership($profile),
                $this->checkTaxCompliance($profile),
                $this->checkSocialSecurityCompliance($profile),
            ],
        );
    }

    /**
     * Ingresos anuales netos de la unidad entre 2 y 7,5 veces el IPREM.
     *
     * El límite inferior existe porque el programa es de alquiler asequible, no de
     * vivienda de emergencia: por debajo de 2 IPREM la vía adecuada es otra. Conviene
     * que el sistema lo explique así, porque para el ciudadano es contraintuitivo que
     * le excluyan por ingresos bajos.
     */
    private function checkIncome(ApplicantProfile $profile): CriterionResult
    {
        $iprem = Money::fromEuros(self::IPREM_ANUAL_EUROS);
        $floor = $iprem->times(self::MIN_IPREM_MULTIPLIER);
        $ceiling = $iprem->times(self::MAX_IPREM_MULTIPLIER);
        $income = $profile->annualNetIncome;

        $evidence = [
            'ingresos_netos_anuales' => $income->toEuros(),
            'iprem_anual' => $iprem->toEuros(),
            'limite_inferior' => $floor->toEuros(),
            'limite_superior' => $ceiling->toEuros(),
        ];

        if ($income->isLessThan($floor)) {
            return CriterionResult::fail(
                'income',
                sprintf(
                    'Los ingresos netos anuales de la unidad de convivencia (%s) no alcanzan el mínimo exigido de 2 veces el IPREM (%s).',
                    $income,
                    $floor,
                ),
                $evidence,
            );
        }

        if ($income->isGreaterThan($ceiling)) {
            return CriterionResult::fail(
                'income',
                sprintf(
                    'Los ingresos netos anuales de la unidad de convivencia (%s) superan el máximo exigido de 7,5 veces el IPREM (%s).',
                    $income,
                    $ceiling,
                ),
                $evidence,
            );
        }

        return CriterionResult::pass(
            'income',
            sprintf('Los ingresos netos anuales (%s) se encuentran dentro del intervalo exigido.', $income),
            $evidence,
        );
    }

    private function checkResidence(ApplicantProfile $profile): CriterionResult
    {
        return $profile->hasLegalResidence
            ? CriterionResult::pass('residence', 'Se acredita nacionalidad española o residencia legal en España.')
            : CriterionResult::fail('residence', 'No se acredita nacionalidad española ni residencia legal en España.');
    }

    /**
     * No ser titular de vivienda en propiedad, salvo que la que se posee no sea
     * utilizable como residencia.
     */
    private function checkPropertyOwnership(ApplicantProfile $profile): CriterionResult
    {
        if (! $profile->ownsProperty) {
            return CriterionResult::pass(
                'property_ownership',
                'Ningún miembro de la unidad de convivencia es titular de vivienda en propiedad.',
            );
        }

        if ($profile->ownedPropertyIsUnusable) {
            return CriterionResult::pass(
                'property_ownership',
                'Se acredita la titularidad de una vivienda no utilizable como residencia, supuesto exceptuado en la convocatoria.',
                ['excepcion_aplicada' => true],
            );
        }

        return CriterionResult::fail(
            'property_ownership',
            'Algún miembro de la unidad de convivencia es titular de una vivienda en propiedad utilizable como residencia.',
            ['excepcion_aplicada' => false],
        );
    }

    private function checkTaxCompliance(ApplicantProfile $profile): CriterionResult
    {
        return $profile->isCurrentWithTaxAuthority
            ? CriterionResult::pass('tax_compliance', 'Se acredita estar al corriente de las obligaciones con la Agencia Tributaria.')
            : CriterionResult::fail('tax_compliance', 'No se acredita estar al corriente de las obligaciones con la Agencia Tributaria.');
    }

    private function checkSocialSecurityCompliance(ApplicantProfile $profile): CriterionResult
    {
        return $profile->isCurrentWithSocialSecurity
            ? CriterionResult::pass('social_security_compliance', 'Se acredita estar al corriente de las obligaciones con la Seguridad Social.')
            : CriterionResult::fail('social_security_compliance', 'No se acredita estar al corriente de las obligaciones con la Seguridad Social.');
    }
}
