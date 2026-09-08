<?php

declare(strict_types=1);

namespace App\Domains\Shared\Outbox;

/**
 * Consume un tipo de evento de la bandeja de salida.
 *
 * Un manejador puede recibir el MISMO evento más de una vez: si la entrega tuvo éxito
 * pero el proceso murió antes de marcarla, el siguiente ciclo lo reintentará. Por eso el
 * contrato exige que `handle()` sea idempotente y le entrega la clave con la que serlo.
 *
 * Es la garantía «al menos una vez». Conseguir «exactamente una vez» de extremo a extremo
 * exigiría colaboración del servicio del otro lado, y los servicios de la Administración
 * no la ofrecen. Fingir lo contrario sería el error.
 */
interface OutboxHandler
{
    /** @return list<string> Nombres de evento que este manejador atiende. */
    public function subscribesTo(): array;

    public function handle(OutboxEvent $event): void;
}
