<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Shared\Outbox\OutboxDispatcher;
use Illuminate\Console\Command;

/**
 * Trabajador de la bandeja de salida.
 *
 * Se ejecuta como proceso de larga duración junto a la aplicación (el servicio `worker`
 * de compose). Es un bucle sencillo a propósito: no hay supervisor, ni orquestador, ni
 * coordinación entre instancias. Se pueden levantar tres copias de este comando y
 * PostgreSQL se encarga de que no se pisen, como demuestra ConcurrenciaDeLaBandejaTest.
 */
final class DispatchOutbox extends Command
{
    protected $signature = 'outbox:work
                            {--once : Procesa un único ciclo y termina. Útil en pruebas y en cron.}
                            {--batch=50 : Eventos por ciclo.}
                            {--sleep=1 : Segundos de espera cuando la bandeja está vacía.}';

    protected $description = 'Entrega los eventos pendientes de la bandeja de salida transaccional.';

    public function handle(OutboxDispatcher $dispatcher): int
    {
        $batch = max(1, (int) $this->option('batch'));
        $sleep = max(0, (int) $this->option('sleep'));

        do {
            $delivered = $dispatcher->dispatchPending($batch);

            if ($delivered > 0) {
                $this->line("Entregados {$delivered} eventos.");
            }

            if ($this->option('once')) {
                $backlog = $dispatcher->backlog();
                $this->line(sprintf(
                    'Pendientes: %d · En bandeja de fallos: %d · Más antiguo: %s',
                    $backlog['pending'],
                    $backlog['dead_lettered'],
                    $backlog['oldest_pending_seconds'] === null
                        ? '—'
                        : $backlog['oldest_pending_seconds'].' s',
                ));

                return self::SUCCESS;
            }

            // Sin eventos, se espera antes de volver a preguntar. Con eventos, se sigue
            // de inmediato: durante el cierre de una convocatoria la bandeja se llena
            // deprisa y no tiene sentido dormir.
            if ($delivered === 0 && $sleep > 0) {
                sleep($sleep);
            }
        } while (true);
    }
}
