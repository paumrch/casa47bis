<?php

declare(strict_types=1);

namespace App\Domains\Shared\Outbox;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Entrega los eventos de la bandeja de salida.
 *
 * CÓMO SE TOMA EL TRABAJO
 *
 * `SELECT … FOR UPDATE SKIP LOCKED`. Varios trabajadores pueden competir por la misma
 * bandeja sin coordinación externa y sin que dos tomen el mismo evento: PostgreSQL
 * resuelve la exclusión. Es la razón, medida y no supuesta, por la que este proyecto no
 * necesita Redis ni un broker: la primitiva ya está en la base de datos que de todos
 * modos hay que operar.
 *
 * QUÉ PASA CUANDO FALLA
 *
 * Retroceso exponencial y, agotados los intentos, bandeja de fallos permanentes. Un
 * evento en esa bandeja NO se descarta: se conserva para que una persona decida. En un
 * procedimiento administrativo, tirar un evento porque falló cinco veces puede significar
 * que un ciudadano nunca se entera de que le adjudicaron una vivienda.
 *
 * Y LO QUE MÁS IMPORTA
 *
 * Una bandeja de salida sin alerta de atasco es una avería silenciosa. Por eso existe
 * `backlog()`, y por eso el ciclo registra los fallos con nivel de aviso: para que la
 * observabilidad los recoja y alguien se entere el mismo día, no cuando reclame el
 * afectado.
 */
final class OutboxDispatcher
{
    private const MAX_ATTEMPTS = 8;

    /** @var array<string, OutboxHandler> */
    private array $handlers = [];

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function register(OutboxHandler $handler): void
    {
        foreach ($handler->subscribesTo() as $eventName) {
            $this->handlers[$eventName] = $handler;
        }
    }

    /**
     * Procesa hasta `$limit` eventos y devuelve cuántos se entregaron.
     */
    public function dispatchPending(int $limit = 50): int
    {
        $delivered = 0;

        foreach ($this->claimPending($limit) as $event) {
            if ($this->deliver($event)) {
                $delivered++;
            }
        }

        return $delivered;
    }

    /**
     * Toma eventos en exclusiva.
     *
     * Cada evento se toma en su propia transacción corta. Mantener abierta una
     * transacción larga mientras se llama a un servicio externo lento es una de las
     * formas más eficaces de tumbar una base de datos.
     *
     * @return list<OutboxEvent>
     */
    private function claimPending(int $limit): array
    {
        return $this->db->transaction(function () use ($limit): array {
            $rows = $this->db->table('outbox_events')
                ->whereNull('delivered_at')
                ->whereNull('dead_lettered_at')
                ->where('available_at', '<=', now())
                ->orderBy('available_at')
                ->limit($limit)
                // `FOR UPDATE SKIP LOCKED` en crudo: el constructor de consultas de
                // Laravel ofrece lockForUpdate(), que espera a que el otro trabajador
                // suelte la fila. Aquí queremos justo lo contrario — saltarla y seguir —,
                // que es lo que permite que varios trabajadores compitan por la misma
                // bandeja sin coordinación externa y sin bloquearse entre sí.
                ->lock('FOR UPDATE SKIP LOCKED')
                ->get();

            $events = [];

            foreach ($rows as $row) {
                /** @var array<string, mixed> $payload */
                $payload = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);

                $events[] = new OutboxEvent(
                    id: (string) $row->id,
                    aggregateType: (string) $row->aggregate_type,
                    aggregateId: (string) $row->aggregate_id,
                    eventName: (string) $row->event_name,
                    payload: $payload,
                    attempts: (int) $row->attempts,
                );

                // Se aparta inmediatamente para que otro trabajador no lo tome mientras
                // este lo está entregando.
                $this->db->table('outbox_events')
                    ->where('id', $row->id)
                    ->update(['available_at' => now()->addMinutes(5)]);
            }

            return $events;
        });
    }

    private function deliver(OutboxEvent $event): bool
    {
        $handler = $this->handlers[$event->eventName] ?? null;

        if ($handler === null) {
            // Un evento sin manejador no es un fallo de entrega: es un fallo de
            // configuración, y se trata como tal. Reintentarlo mil veces no lo arregla.
            $this->deadLetter($event, 'No hay ningún manejador registrado para este evento.');

            return false;
        }

        try {
            $handler->handle($event);

            $this->db->table('outbox_events')
                ->where('id', $event->id)
                ->update(['delivered_at' => now(), 'attempts' => $event->attempts + 1]);

            return true;
        } catch (Throwable $e) {
            $this->recordFailure($event, $e);

            return false;
        }
    }

    private function recordFailure(OutboxEvent $event, Throwable $e): void
    {
        $attempts = $event->attempts + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->deadLetter($event, $e->getMessage(), $attempts);

            return;
        }

        $this->db->table('outbox_events')->where('id', $event->id)->update([
            'attempts' => $attempts,
            'last_error' => $e->getMessage(),
            'available_at' => now()->addSeconds(self::backoffSeconds($attempts)),
        ]);

        $this->logger->warning('Fallo al entregar un evento de la bandeja de salida.', [
            'outbox_event_id' => $event->id,
            'event_name' => $event->eventName,
            'attempts' => $attempts,
            'error' => $e->getMessage(),
        ]);
    }

    private function deadLetter(OutboxEvent $event, string $reason, ?int $attempts = null): void
    {
        $this->db->table('outbox_events')->where('id', $event->id)->update([
            'attempts' => $attempts ?? $event->attempts + 1,
            'last_error' => $reason,
            'dead_lettered_at' => now(),
        ]);

        // Nivel de error, no de aviso: esto exige que una persona mire.
        $this->logger->error('Evento de la bandeja de salida enviado a la bandeja de fallos.', [
            'outbox_event_id' => $event->id,
            'event_name' => $event->eventName,
            'aggregate_id' => $event->aggregateId,
            'reason' => $reason,
        ]);
    }

    /** Retroceso exponencial con techo de una hora: 2s, 4s, 8s… hasta 3.600s. */
    private static function backoffSeconds(int $attempts): int
    {
        return (int) min(3600, 2 ** $attempts);
    }

    /**
     * Estado de la bandeja, para la observabilidad.
     *
     * `oldest_pending_seconds` es la métrica sobre la que se alerta: el número de
     * pendientes puede ser alto y estar todo bien —un pico de cierre de convocatoria—,
     * pero un evento esperando desde hace media hora significa que algo está roto.
     *
     * @return array{pending: int, dead_lettered: int, oldest_pending_seconds: int|null}
     */
    public function backlog(): array
    {
        $oldest = $this->db->table('outbox_events')
            ->whereNull('delivered_at')
            ->whereNull('dead_lettered_at')
            ->min('occurred_at');

        return [
            'pending' => $this->db->table('outbox_events')
                ->whereNull('delivered_at')->whereNull('dead_lettered_at')->count(),
            'dead_lettered' => $this->db->table('outbox_events')
                ->whereNotNull('dead_lettered_at')->count(),
            'oldest_pending_seconds' => $oldest === null
                ? null
                : (int) now()->diffInSeconds(new \DateTimeImmutable((string) $oldest), absolute: true),
        ];
    }
}
