<?php

declare(strict_types=1);

namespace App\Domains\Shared\Outbox;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

/**
 * Bandeja de salida transaccional.
 *
 * EL PROBLEMA QUE RESUELVE, CON UN EJEMPLO CONCRETO
 *
 * Un ciudadano presenta su solicitud. Hay que (a) registrarla y (b) notificárselo de
 * forma fehaciente, lo que hace correr plazos. Si se llama al servicio de notificación
 * dentro de la transacción y esa transacción se deshace, se ha notificado algo que no
 * existe. Si se llama después de confirmar y el proceso muere entre medias, la solicitud
 * consta registrada y nadie la notificará jamás.
 *
 * Ninguno de los dos fallos es hipotético, y el segundo es especialmente desagradable
 * porque es silencioso: nadie se entera hasta que un ciudadano reclama.
 *
 * La solución no es un bus de mensajes. Es escribir el evento **en la misma transacción**
 * que el cambio de estado. O se confirman los dos, o ninguno. Después, un trabajador lee
 * la bandeja y entrega, con reintentos.
 *
 * POR QUÉ NO KAFKA
 *
 * Un bus resuelve un problema distinto: distribuir gran volumen a muchos consumidores
 * desacoplados. Aquí hay pocos consumidores, conocidos, y miles de eventos, no millones
 * por segundo. Lo que hace falta —atomicidad entre el cambio y la intención de
 * notificar— lo da la transacción, no el bus. Añadir un bus no resolvería este problema:
 * lo tendría igual, más un componente que operar.
 */
final readonly class OutboxRecorder
{
    public function __construct(private ConnectionInterface $db) {}

    /**
     * Encola un evento para entrega posterior.
     *
     * Igual que la auditoría, exige transacción abierta. Es toda la razón de ser del
     * patrón: fuera de una transacción, esta clase no aporta nada sobre una llamada
     * directa.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(
        string $aggregateType,
        string $aggregateId,
        string $eventName,
        array $payload,
        ?\DateTimeInterface $availableAt = null,
    ): string {
        if ($this->db->transactionLevel() < 1) {
            throw new \LogicException(
                'OutboxRecorder::publish() debe ejecutarse dentro de una transacción. '.
                'Fuera de ella el patrón no ofrece ninguna garantía y sobra.'
            );
        }

        $id = (string) Str::uuid7();

        $this->db->table('outbox_events')->insert([
            'id' => $id,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_name' => $eventName,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'occurred_at' => now(),
            'available_at' => $availableAt ?? now(),
            'attempts' => 0,
        ]);

        return $id;
    }
}
