<?php

declare(strict_types=1);

namespace App\Domains\Audit;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

/**
 * Registro de auditoría inmutable y encadenado.
 *
 * QUÉ PROBLEMA RESUELVE
 *
 * Una tabla de auditoría corriente demuestra qué pasó, pero no demuestra que no se haya
 * tocado después. Si alguien con acceso a la base borra la fila que le incrimina, no
 * queda rastro: la tabla sigue siendo coherente.
 *
 * Aquí cada fila incorpora el hash de la anterior. Borrar o modificar una fila rompe la
 * cadena en ese punto y en todos los posteriores, y `verifyChain()` lo detecta. Es una
 * exigencia del ENS sobre integridad de la traza, y en un sistema que decide quién
 * accede a una vivienda pública es también sentido común.
 *
 * EL PRECIO, DICHO CLARAMENTE
 *
 * Encadenar obliga a serializar las escrituras: dos procesos no pueden calcular a la vez
 * el hash siguiente sin arriesgarse a partir del mismo eslabón. Se resuelve con un
 * cerrojo consultivo de PostgreSQL, que es un punto de serialización real.
 *
 * A los volúmenes de este sistema —del orden de 10⁷ eventos en cuatro años, con picos de
 * decenas por segundo en el cierre de una convocatoria— es asumible y se ha medido. Si
 * algún día dejara de serlo, la salida conocida es encadenar por día o por entidad en
 * lugar de globalmente: se pierde la detección de borrado de un día entero, pero se gana
 * concurrencia. Es una decisión a tomar con datos, y hoy los datos no la piden.
 */
final readonly class AuditRecorder
{
    /** Identificador del cerrojo consultivo. Constante arbitraria pero estable. */
    private const CHAIN_LOCK_KEY = 8_472_001;

    public function __construct(private ConnectionInterface $db) {}

    /**
     * Registra un evento.
     *
     * DEBE invocarse dentro de una transacción ya abierta por quien llama. No abre una
     * propia a propósito: la auditoría tiene que confirmarse con el cambio que describe,
     * o no confirmarse en absoluto. Una auditoría que sobrevive a un cambio revertido
     * miente tanto como una que falta.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        string $entityType,
        ?string $entityId,
        AuditActor $actor,
        array $metadata = [],
    ): string {
        $this->assertInTransaction();

        // Serializa el cálculo del eslabón siguiente. El cerrojo se libera solo al
        // confirmar o deshacer la transacción.
        $this->db->statement('SELECT pg_advisory_xact_lock(?)', [self::CHAIN_LOCK_KEY]);

        $id = (string) Str::uuid7();

        // Se formatea explícitamente en UTC y con microsegundos. Si se pasara el objeto
        // de fecha directamente, Laravel lo enlazaría con el formato 'Y-m-d H:i:s' y
        // PostgreSQL guardaría los microsegundos a cero: el hash calculado al escribir
        // no coincidiría con el recalculado al verificar, y la cadena parecería rota
        // desde el primer eslabón. Es un detalle diminuto que invalidaría toda la
        // garantía de integridad, así que queda escrito aquí.
        $occurredAt = now()->utc()->format('Y-m-d H:i:s.u');
        $previousHash = $this->lastHash();

        $row = [
            'id' => $id,
            'occurred_at' => $occurredAt,
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $actor->ipAddress,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'previous_hash' => $previousHash,
        ];

        $row['hash'] = self::hashFor($row);

        $this->db->table('audit_events')->insert($row);

        return $id;
    }

    /**
     * Comprueba la integridad de la cadena.
     *
     * Pensado para ejecutarse periódicamente desde un trabajo programado y ante
     * cualquier requerimiento. Devuelve el identificador del primer eslabón roto, o
     * null si la cadena está intacta.
     */
    public function verifyChain(): ?string
    {
        $previousHash = null;

        foreach ($this->db->table('audit_events')->orderBy('occurred_at')->orderBy('id')->cursor() as $event) {
            $row = (array) $event;

            if ($row['previous_hash'] !== $previousHash) {
                return (string) $row['id'];
            }

            if (self::hashFor($row) !== $row['hash']) {
                return (string) $row['id'];
            }

            $previousHash = $row['hash'];
        }

        return null;
    }

    private function lastHash(): ?string
    {
        $last = $this->db->table('audit_events')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->value('hash');

        return $last === null ? null : (string) $last;
    }

    /**
     * Huella del evento.
     *
     * El orden de los campos es fijo y explícito: si dependiera del orden de las claves
     * del array, un cambio inocente en el código rompería todas las verificaciones
     * históricas.
     *
     * @param  array<string, mixed>  $row
     */
    private static function hashFor(array $row): string
    {
        return hash('sha256', implode('|', [
            (string) $row['id'],
            self::normaliseTimestamp($row['occurred_at']),
            (string) ($row['actor_type'] ?? ''),
            (string) ($row['actor_id'] ?? ''),
            (string) $row['action'],
            (string) $row['entity_type'],
            (string) ($row['entity_id'] ?? ''),
            (string) ($row['ip_address'] ?? ''),
            self::canonicalJson($row['metadata'] ?? null),
            (string) ($row['previous_hash'] ?? ''),
        ]));
    }

    /**
     * Forma canónica del JSON de metadatos.
     *
     * Imprescindible: la columna es `jsonb`, y PostgreSQL NO conserva el texto que se le
     * envía. Reordena las claves, elimina espacios y normaliza los números. Al releer,
     * el mismo contenido produce una cadena distinta de la que se insertó, y el hash
     * calculado sobre el texto crudo no coincidiría jamás. La cadena parecería rota en
     * el primer eslabón sin que nadie la hubiera tocado.
     *
     * Se resuelve ordenando las claves de forma recursiva y volviendo a codificar con
     * banderas fijas, en la escritura y en la verificación.
     */
    private static function canonicalJson(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $decoded = is_array($value)
            ? $value
            : json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            return (string) $value;
        }

        self::ksortRecursive($decoded);

        return json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<array-key, mixed> $array */
    private static function ksortRecursive(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                self::ksortRecursive($value);
            }
        }
    }

    /**
     * Normaliza la marca de tiempo a UTC con microsegundos.
     *
     * Sin esto la verificación fallaría siempre: al insertar tenemos un objeto de fecha
     * y al releer PostgreSQL devuelve una cadena con desplazamiento horario. Dos
     * representaciones del mismo instante producen hashes distintos, y la cadena
     * parecería rota sin estarlo.
     */
    private static function normaliseTimestamp(mixed $value): string
    {
        $date = $value instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($value)
            : new \DateTimeImmutable((string) $value);

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
    }

    private function assertInTransaction(): void
    {
        if ($this->db->transactionLevel() < 1) {
            throw new \LogicException(
                'AuditRecorder::record() debe ejecutarse dentro de una transacción. '.
                'La auditoría se confirma junto al cambio que describe, o no se confirma.'
            );
        }
    }
}
