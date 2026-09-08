<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Shared;

use App\Domains\Shared\Outbox\OutboxDispatcher;
use App\Domains\Shared\Outbox\OutboxEvent;
use App\Domains\Shared\Outbox\OutboxHandler;
use App\Domains\Shared\Outbox\OutboxRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * La bandeja de salida transaccional, de extremo a extremo.
 *
 * Sustituye a un bus de mensajes en un sistema con pocos consumidores conocidos y
 * volúmenes de miles de eventos. Estas pruebas comprueban lo único que hay que
 * comprobar: que un evento se entrega, que un fallo se reintenta con retroceso, que
 * tras agotar los intentos NO se descarta sino que espera a una persona, y que la
 * clave de idempotencia se mantiene entre reintentos.
 */
final class BandejaDeSalidaTest extends TestCase
{
    use RefreshDatabase;

    private function publicar(string $eventName = 'application.submitted'): string
    {
        return DB::transaction(
            fn (): string => (new OutboxRecorder(DB::connection()))->publish(
                'application',
                (string) Str::uuid7(),
                $eventName,
                ['dato' => 'valor'],
            )
        );
    }

    private function despachador(): OutboxDispatcher
    {
        return new OutboxDispatcher(DB::connection(), Log::getLogger());
    }

    #[Test]
    public function un_evento_entregado_queda_marcado_y_no_se_repite(): void
    {
        $id = $this->publicar();

        $manejador = new class implements OutboxHandler
        {
            /** @var list<string> */
            public array $recibidos = [];

            public function subscribesTo(): array
            {
                return ['application.submitted'];
            }

            public function handle(OutboxEvent $event): void
            {
                $this->recibidos[] = $event->id;
            }
        };

        $despachador = $this->despachador();
        $despachador->register($manejador);

        $this->assertSame(1, $despachador->dispatchPending());
        $this->assertSame([$id], $manejador->recibidos);
        $this->assertNotNull(DB::table('outbox_events')->where('id', $id)->value('delivered_at'));

        // Un segundo ciclo no vuelve a entregarlo.
        $this->assertSame(0, $despachador->dispatchPending());
        $this->assertCount(1, $manejador->recibidos);
    }

    #[Test]
    public function un_fallo_se_reintenta_mas_tarde_y_conserva_el_motivo(): void
    {
        $id = $this->publicar();

        $despachador = $this->despachador();
        $despachador->register($this->manejadorQueFalla());

        $this->assertSame(0, $despachador->dispatchPending());

        $row = DB::table('outbox_events')->where('id', $id)->first();

        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->attempts);
        $this->assertNull($row->delivered_at);
        $this->assertNull($row->dead_lettered_at);
        $this->assertStringContainsString('servicio caído', (string) $row->last_error);
        $this->assertTrue(
            now()->lessThan($row->available_at),
            'Tras un fallo el evento debe reprogramarse al futuro, no reintentarse en bucle.',
        );
    }

    #[Test]
    public function tras_agotar_los_intentos_el_evento_espera_a_una_persona_y_no_se_descarta(): void
    {
        $id = $this->publicar();

        $despachador = $this->despachador();
        $despachador->register($this->manejadorQueFalla());

        // Se fuerzan los intentos al límite en lugar de esperar el retroceso real.
        DB::table('outbox_events')->where('id', $id)->update(['attempts' => 7]);

        $despachador->dispatchPending();

        $row = DB::table('outbox_events')->where('id', $id)->first();

        $this->assertNotNull($row);
        $this->assertNotNull(
            $row->dead_lettered_at,
            'El evento debe pasar a la bandeja de fallos permanentes.',
        );
        $this->assertNotNull(
            DB::table('outbox_events')->where('id', $id)->first(),
            'Un evento fallido NO se borra: en un procedimiento administrativo puede '.
            'significar que un ciudadano nunca supo que le adjudicaron una vivienda.',
        );
    }

    #[Test]
    public function un_evento_sin_manejador_no_se_reintenta_en_bucle(): void
    {
        $id = $this->publicar('evento.sin.destino');

        $this->despachador()->dispatchPending();

        $row = DB::table('outbox_events')->where('id', $id)->first();

        $this->assertNotNull($row);
        $this->assertNotNull($row->dead_lettered_at);
        $this->assertStringContainsString('manejador', (string) $row->last_error);
    }

    #[Test]
    public function la_clave_de_idempotencia_es_estable_entre_reintentos(): void
    {
        $id = $this->publicar();

        $claves = [];

        $manejador = new class($claves) implements OutboxHandler
        {
            /** @param list<string> $claves */
            public function __construct(public array &$claves) {}

            public function subscribesTo(): array
            {
                return ['application.submitted'];
            }

            public function handle(OutboxEvent $event): void
            {
                $this->claves[] = $event->idempotencyKey();

                throw new RuntimeException('servicio caído');
            }
        };

        $despachador = $this->despachador();
        $despachador->register($manejador);

        $despachador->dispatchPending();
        DB::table('outbox_events')->where('id', $id)->update(['available_at' => now()->subMinute()]);
        $despachador->dispatchPending();

        $this->assertSame(
            [$id, $id],
            $manejador->claves,
            'Si la clave cambiara entre reintentos, un servicio externo podría notificar '.
            'dos veces al mismo ciudadano. Eso es un problema jurídico, no una molestia.',
        );
    }

    #[Test]
    public function el_atasco_es_observable(): void
    {
        $this->publicar();
        $this->publicar();

        $backlog = $this->despachador()->backlog();

        $this->assertSame(2, $backlog['pending']);
        $this->assertSame(0, $backlog['dead_lettered']);
        $this->assertNotNull(
            $backlog['oldest_pending_seconds'],
            'Sin esta métrica, una bandeja atascada es una avería silenciosa.',
        );
    }

    private function manejadorQueFalla(): OutboxHandler
    {
        return new class implements OutboxHandler
        {
            public function subscribesTo(): array
            {
                return ['application.submitted'];
            }

            public function handle(OutboxEvent $event): void
            {
                throw new RuntimeException('servicio caído');
            }
        };
    }
}
