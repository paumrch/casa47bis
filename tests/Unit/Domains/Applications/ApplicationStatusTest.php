<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Applications;

use App\Domains\Applications\ApplicationStatus as S;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * El procedimiento administrativo, comprobado.
 *
 * Estas pruebas no verifican código: verifican que el flujo que ejecuta la aplicación
 * es el que la norma describe. Por eso están escritas en términos del procedimiento y
 * no en términos de la implementación.
 */
final class ApplicationStatusTest extends TestCase
{
    #[Test]
    public function una_solicitud_nace_en_borrador_y_solo_puede_presentarse_o_desistirse(): void
    {
        $this->assertSame(
            [S::Submitted, S::Withdrawn],
            S::Draft->allowedNext(),
        );
    }

    #[Test]
    public function un_borrador_no_produce_efectos_juridicos_pero_todo_lo_demas_si(): void
    {
        $this->assertFalse(S::Draft->hasLegalEffect());

        foreach (S::cases() as $status) {
            if ($status !== S::Draft) {
                $this->assertTrue(
                    $status->hasLegalEffect(),
                    "El estado «{$status->value}» debería producir efectos jurídicos.",
                );
            }
        }
    }

    /** @return array<string, array{S}> */
    public static function estadosFinales(): array
    {
        return [
            'excluida' => [S::Ineligible],
            'renuncia' => [S::Declined],
            'contratada' => [S::Contracted],
            'desistida' => [S::Withdrawn],
            'caducada' => [S::Expired],
        ];
    }

    #[Test]
    #[DataProvider('estadosFinales')]
    public function un_expediente_cerrado_no_vuelve_a_abrirse(S $final): void
    {
        $this->assertTrue($final->isFinal());
        $this->assertSame([], $final->allowedNext());

        foreach (S::cases() as $target) {
            $this->assertFalse(
                $final->canTransitionTo($target),
                "No debería poderse pasar de «{$final->value}» a «{$target->value}».",
            );
        }
    }

    #[Test]
    public function no_se_puede_adjudicar_una_solicitud_que_no_ha_sido_baremada(): void
    {
        $this->assertFalse(S::Submitted->canTransitionTo(S::Awarded));
        $this->assertFalse(S::Eligible->canTransitionTo(S::Awarded));
        $this->assertTrue(S::Scored->canTransitionTo(S::Awarded));
    }

    #[Test]
    public function no_se_puede_saltar_la_comprobacion_de_requisitos(): void
    {
        $this->assertFalse(S::Submitted->canTransitionTo(S::Eligible));
        $this->assertFalse(S::Submitted->canTransitionTo(S::Ineligible));
        $this->assertTrue(S::Submitted->canTransitionTo(S::UnderVerification));
    }

    #[Test]
    public function el_requerimiento_de_documentacion_puede_caducar(): void
    {
        $this->assertTrue(S::AwaitingApplicant->canTransitionTo(S::Expired));
        $this->assertTrue(S::AwaitingApplicant->canTransitionTo(S::UnderVerification));
    }

    #[Test]
    public function una_adjudicacion_no_aceptada_caduca_o_se_rechaza(): void
    {
        $this->assertEqualsCanonicalizing(
            [S::Accepted, S::Declined, S::Expired],
            S::Awarded->allowedNext(),
        );
    }

    #[Test]
    public function todos_los_estados_son_alcanzables_desde_el_borrador(): void
    {
        $reachable = [S::Draft];
        $queue = [S::Draft];

        while ($queue !== []) {
            foreach (array_shift($queue)->allowedNext() as $next) {
                if (! in_array($next, $reachable, strict: true)) {
                    $reachable[] = $next;
                    $queue[] = $next;
                }
            }
        }

        $unreachable = array_values(array_filter(
            S::cases(),
            static fn (S $s): bool => ! in_array($s, $reachable, strict: true),
        ));

        $this->assertSame(
            [],
            $unreachable,
            'Hay estados definidos a los que el procedimiento nunca llega: '
                .implode(', ', array_map(static fn (S $s): string => $s->value, $unreachable)),
        );
    }

    #[Test]
    public function el_procedimiento_no_tiene_callejones_sin_salida_no_declarados(): void
    {
        foreach (S::cases() as $status) {
            if ($status->allowedNext() === []) {
                $this->assertTrue(
                    $status->isFinal(),
                    "«{$status->value}» no tiene salida pero no está declarado como estado final.",
                );
            }
        }
    }

    #[Test]
    public function todo_estado_tiene_etiqueta_para_el_ciudadano(): void
    {
        foreach (S::cases() as $status) {
            $this->assertNotSame('', trim($status->label()));
        }
    }
}
