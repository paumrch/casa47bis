<?php

declare(strict_types=1);

namespace App\Domains\Eligibility\Values;

/**
 * Veredicto completo de elegibilidad.
 *
 * Se evalúan SIEMPRE todos los criterios, aunque el primero ya haya fallado. Es
 * deliberado y va contra la intuición de un programador: cortocircuitar sería más
 * eficiente, pero dejaría al ciudadano recibiendo una exclusión, subsanando ese motivo
 * y volviendo a ser excluido por el siguiente. Se le dicen todos a la vez.
 */
final readonly class EligibilityResult
{
    /** @param list<CriterionResult> $criteria */
    public function __construct(
        public string $ruleSetVersion,
        public array $criteria,
    ) {}

    public function isEligible(): bool
    {
        foreach ($this->criteria as $criterion) {
            if (! $criterion->passed) {
                return false;
            }
        }

        return true;
    }

    /** @return list<CriterionResult> */
    public function failures(): array
    {
        return array_values(array_filter($this->criteria, static fn (CriterionResult $c): bool => ! $c->passed));
    }

    /** Motivación de la resolución, lista para incorporar al expediente. */
    public function motivation(): string
    {
        if ($this->isEligible()) {
            return 'La solicitud cumple todos los requisitos de acceso establecidos en la convocatoria.';
        }

        $reasons = array_map(
            static fn (CriterionResult $c): string => '- '.$c->explanation,
            $this->failures(),
        );

        return "La solicitud no cumple los siguientes requisitos de acceso:\n".implode("\n", $reasons);
    }

    /** @return array<string, mixed> Traza completa, para archivar junto a la solicitud. */
    public function toAuditTrail(): array
    {
        return [
            'rule_set_version' => $this->ruleSetVersion,
            'eligible' => $this->isEligible(),
            'criteria' => array_map(static fn (CriterionResult $c): array => [
                'code' => $c->code,
                'passed' => $c->passed,
                'explanation' => $c->explanation,
                'evidence' => $c->evidence,
            ], $this->criteria),
        ];
    }
}
