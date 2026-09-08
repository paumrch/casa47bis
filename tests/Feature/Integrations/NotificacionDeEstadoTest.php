<?php

declare(strict_types=1);

namespace Tests\Feature\Integrations;

use App\Domains\Notifications\ApplicationStatusNotifier;
use App\Domains\Shared\Outbox\OutboxEvent;
use App\Integrations\Testing\RecordingNotificationGateway;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Notificación fehaciente frente a aviso de cortesía.
 *
 * Confundirlas es uno de los errores más graves que puede cometer un sistema
 * administrativo. Notificar por correo electrónico un acto que abre plazo de recurso deja
 * al ciudadano sin garantía de haberse enterado; y meter avisos de cortesía por la vía
 * fehaciente satura un canal con efectos jurídicos.
 *
 * Por eso la decisión vive en un único sitio y aquí se comprueba acto por acto.
 */
final class NotificacionDeEstadoTest extends TestCase
{
    private RecordingNotificationGateway $gateway;

    private ApplicationStatusNotifier $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new RecordingNotificationGateway;
        $this->notifier = new ApplicationStatusNotifier($this->gateway);
    }

    /** @param array<string, mixed> $payload */
    private function evento(string $name, array $payload = []): OutboxEvent
    {
        return new OutboxEvent(
            id: 'evt-1',
            aggregateType: 'application',
            aggregateId: 'app-1',
            eventName: $name,
            payload: $payload,
            attempts: 0,
        );
    }

    #[Test]
    public function el_acuse_de_recibo_es_un_aviso_y_no_abre_plazo(): void
    {
        $this->notifier->handle($this->evento('application.submitted', ['email' => 'persona@example.test']));

        $this->assertCount(1, $this->gateway->advisory);
        $this->assertCount(0, $this->gateway->formal, 'Recibir una solicitud no abre ningún plazo.');
    }

    #[Test]
    public function el_requerimiento_de_documentacion_es_fehaciente(): void
    {
        $this->notifier->handle($this->evento('application.documents_required', [
            'checks' => ['income', 'tax_compliance'],
        ]));

        $this->assertCount(1, $this->gateway->formal, 'Un requerimiento abre plazo de subsanación.');
        $this->assertCount(0, $this->gateway->advisory);
    }

    #[Test]
    public function el_requerimiento_dice_en_castellano_que_hay_que_aportar(): void
    {
        $this->notifier->handle($this->evento('application.documents_required', [
            'checks' => ['income', 'social_security_compliance'],
        ]));

        $cuerpo = $this->gateway->formal[0]['body'];

        $this->assertStringContainsString('justificación de ingresos', $cuerpo);
        $this->assertStringContainsString('Seguridad Social', $cuerpo);
        $this->assertStringNotContainsString(
            'income',
            $cuerpo,
            'Al ciudadano no se le escribe con los códigos internos del sistema.',
        );
    }

    #[Test]
    public function la_resolucion_de_exclusion_es_fehaciente_y_va_motivada(): void
    {
        $this->notifier->handle($this->evento('application.ineligible', [
            'motivation' => 'La solicitud no cumple los siguientes requisitos de acceso: …',
        ]));

        $this->assertCount(1, $this->gateway->formal, 'Una exclusión abre plazo de recurso.');
        $this->assertStringContainsString('no cumple', $this->gateway->formal[0]['body']);
    }

    #[Test]
    public function la_resolucion_de_admision_tambien_es_fehaciente(): void
    {
        $this->notifier->handle($this->evento('application.eligible', ['motivation' => 'Cumple todos los requisitos.']));

        $this->assertCount(1, $this->gateway->formal);
    }

    #[Test]
    public function un_fallo_del_servicio_se_propaga_para_que_la_bandeja_reintente(): void
    {
        $this->gateway->shouldFail = true;

        $this->expectException(\RuntimeException::class);

        // El manejador NO captura el error: si lo hiciera, el despachador daría el evento
        // por entregado y la notificación se perdería en silencio.
        $this->notifier->handle($this->evento('application.eligible', ['motivation' => 'x']));
    }

    #[Test]
    public function atiende_exactamente_los_eventos_que_declara(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                'application.submitted',
                'application.documents_required',
                'application.eligible',
                'application.ineligible',
            ],
            $this->notifier->subscribesTo(),
        );
    }
}
