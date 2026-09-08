<?php

declare(strict_types=1);

namespace App\Domains\Shared\Outbox;

/**
 * Un evento pendiente de entregar, ya leído de la bandeja.
 *
 * @phpstan-type Payload array<string, mixed>
 */
final readonly class OutboxEvent
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $aggregateType,
        public string $aggregateId,
        public string $eventName,
        public array $payload,
        public int $attempts,
    ) {}

    /**
     * Clave de idempotencia que se entrega al servicio externo.
     *
     * Es el identificador del propio evento, y es estable entre reintentos. Un servicio
     * que la respete no duplicará la notificación aunque nosotros la enviemos dos veces
     * porque su respuesta se perdió por el camino. Sin esto, cualquier reintento puede
     * traducirse en dos notificaciones fehacientes al mismo ciudadano, que es un
     * problema jurídico, no una molestia.
     */
    public function idempotencyKey(): string
    {
        return $this->id;
    }
}
