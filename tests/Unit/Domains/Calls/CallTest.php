<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Calls;

use App\Domains\Calls\CallStatus;
use App\Domains\Calls\Models\Call;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lógica de plazos de una convocatoria. No toca base de datos: son
 * comparaciones de fechas y estado sobre un modelo en memoria, así que la
 * prueba unitaria basta y es instantánea.
 */
final class CallTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function convocatoria(CallStatus $status, string $opensAt, string $closesAt): Call
    {
        return new Call([
            'reference_code' => 'CONV-TEST',
            'name' => 'Convocatoria de prueba',
            'eligibility_rule_version' => '2026.1',
            'scoring_rule_version' => '2026.1',
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'status' => $status,
        ]);
    }

    #[Test]
    public function esta_abierta_dentro_de_plazo_y_con_estado_open(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $call = $this->convocatoria(CallStatus::Open, '2026-06-01 00:00:00', '2026-07-01 00:00:00');

        $this->assertTrue($call->isOpen());
        $this->assertFalse($call->hasClosed());
    }

    #[Test]
    public function no_esta_abierta_si_el_estado_administrativo_no_es_open_aunque_las_fechas_lo_permitan(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $call = $this->convocatoria(CallStatus::Draft, '2026-06-01 00:00:00', '2026-07-01 00:00:00');

        $this->assertFalse($call->isOpen());
    }

    #[Test]
    public function en_el_minuto_exacto_de_apertura_ya_esta_abierta(): void
    {
        Carbon::setTestNow('2026-06-01 00:00:00');

        $call = $this->convocatoria(CallStatus::Open, '2026-06-01 00:00:00', '2026-07-01 00:00:00');

        $this->assertTrue($call->isOpen());
    }

    #[Test]
    public function un_segundo_antes_de_abrir_todavia_no_esta_abierta(): void
    {
        Carbon::setTestNow('2026-05-31 23:59:59');

        $call = $this->convocatoria(CallStatus::Open, '2026-06-01 00:00:00', '2026-07-01 00:00:00');

        $this->assertFalse($call->isOpen());
    }

    #[Test]
    public function en_el_minuto_exacto_de_cierre_todavia_esta_abierta_y_no_ha_cerrado(): void
    {
        Carbon::setTestNow('2026-07-01 00:00:00');

        $call = $this->convocatoria(CallStatus::Open, '2026-06-01 00:00:00', '2026-07-01 00:00:00');

        $this->assertTrue($call->isOpen());
        $this->assertFalse($call->hasClosed());
    }

    #[Test]
    public function un_segundo_despues_del_cierre_ha_cerrado_y_ya_no_esta_abierta(): void
    {
        Carbon::setTestNow('2026-07-01 00:00:01');

        $call = $this->convocatoria(CallStatus::Open, '2026-06-01 00:00:00', '2026-07-01 00:00:00');

        $this->assertFalse($call->isOpen());
        $this->assertTrue($call->hasClosed());
    }

    #[Test]
    public function los_dias_restantes_se_redondean_al_alza(): void
    {
        Carbon::setTestNow('2026-06-01 00:00:00');

        // Quedan 36 horas: menos de dos días completos, pero más de uno.
        $call = $this->convocatoria(CallStatus::Open, '2026-05-01 00:00:00', '2026-06-02 12:00:00');

        $this->assertSame(2, $call->daysRemaining());
    }

    #[Test]
    public function no_hay_dias_restantes_si_la_convocatoria_no_esta_abierta(): void
    {
        Carbon::setTestNow('2026-08-01 00:00:00');

        $call = $this->convocatoria(CallStatus::Closed, '2026-06-01 00:00:00', '2026-07-01 00:00:00');

        $this->assertNull($call->daysRemaining());
    }
}
