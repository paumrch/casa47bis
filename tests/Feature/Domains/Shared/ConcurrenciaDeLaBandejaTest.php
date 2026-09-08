<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Shared;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dos trabajadores no toman el mismo evento.
 *
 * POR QUÉ ESTA PRUEBA ES LA MÁS IMPORTANTE DE LA BANDEJA DE SALIDA
 *
 * Todo el argumento de que este sistema no necesita Redis ni un broker se apoya en una
 * sola primitiva de PostgreSQL: `SELECT … FOR UPDATE SKIP LOCKED`. Si esa primitiva no
 * hiciera lo que se afirma, la arquitectura propuesta tendría un fallo grave -dos
 * trabajadores notificando dos veces al mismo ciudadano- y habría que reintroducir el
 * componente que se ha decidido no tener.
 *
 * Así que no se afirma: se comprueba, con dos conexiones reales y simultáneas.
 *
 * NO usa RefreshDatabase a propósito. Ese rasgo envuelve cada prueba en una transacción,
 * y entonces la segunda conexión no vería nada de lo que escribe la primera: la prueba
 * pasaría sin demostrar nada. Es exactamente la clase de prueba que da confianza sin
 * darla, así que aquí se paga el precio de migrar y limpiar a mano.
 */
final class ConcurrenciaDeLaBandejaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);

        // Segunda conexión al mismo servidor, para simular un segundo trabajador.
        config(['database.connections.worker2' => config('database.connections.pgsql')]);
    }

    protected function tearDown(): void
    {
        DB::table('outbox_events')->delete();
        DB::purge('worker2');

        parent::tearDown();
    }

    private function encolar(int $cuantos): void
    {
        $filas = [];

        for ($i = 0; $i < $cuantos; $i++) {
            $filas[] = [
                'id' => (string) Str::uuid7(),
                'aggregate_type' => 'application',
                'aggregate_id' => (string) Str::uuid7(),
                'event_name' => 'application.submitted',
                'payload' => json_encode(['n' => $i], JSON_THROW_ON_ERROR),
                'occurred_at' => now(),
                'available_at' => now(),
                'attempts' => 0,
            ];
        }

        DB::table('outbox_events')->insert($filas);
    }

    /** @return array<int, string> */
    private function tomarCon(string $connection, int $limit): array
    {
        return DB::connection($connection)
            ->table('outbox_events')
            ->whereNull('delivered_at')
            ->whereNull('dead_lettered_at')
            ->where('available_at', '<=', now())
            ->orderBy('available_at')
            ->limit($limit)
            ->lock('FOR UPDATE SKIP LOCKED')
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();
    }

    #[Test]
    public function dos_trabajadores_simultaneos_se_reparten_los_eventos_sin_solaparse(): void
    {
        $this->encolar(10);

        // El trabajador 1 abre transacción y toma 5. Los deja bloqueados.
        DB::beginTransaction();
        $delPrimero = $this->tomarCon('pgsql', 5);

        // El trabajador 2, en paralelo, pide 5. No puede esperar a que el primero
        // termine: los salta y se lleva los siguientes.
        DB::connection('worker2')->beginTransaction();
        $delSegundo = $this->tomarCon('worker2', 5);

        DB::connection('worker2')->rollBack();
        DB::rollBack();

        $this->assertCount(5, $delPrimero);
        $this->assertCount(5, $delSegundo);

        $this->assertSame(
            [],
            array_intersect($delPrimero, $delSegundo),
            'Dos trabajadores han tomado el mismo evento. Sin exclusión, un ciudadano '.
            'recibiría dos notificaciones fehacientes del mismo acto.',
        );

        $this->assertCount(
            10,
            array_unique([...$delPrimero, ...$delSegundo]),
            'Entre los dos deberían haberse repartido los diez eventos.',
        );
    }

    #[Test]
    public function un_trabajador_no_se_queda_esperando_a_otro(): void
    {
        $this->encolar(3);

        DB::beginTransaction();
        $this->tomarCon('pgsql', 3);

        // Los tres están bloqueados. El segundo trabajador NO debe quedarse esperando:
        // debe volver de inmediato con las manos vacías y seguir haciendo su trabajo.
        // Ésa es la diferencia entre SKIP LOCKED y un lockForUpdate normal, y es la
        // razón de que se use SQL en crudo en el despachador.
        $inicio = microtime(true);
        DB::connection('worker2')->beginTransaction();
        $delSegundo = $this->tomarCon('worker2', 3);
        $transcurrido = microtime(true) - $inicio;

        DB::connection('worker2')->rollBack();
        DB::rollBack();

        $this->assertSame([], $delSegundo);
        $this->assertLessThan(
            1.0,
            $transcurrido,
            'El segundo trabajador se ha quedado esperando. Con SKIP LOCKED debe '.
            'volver inmediatamente.',
        );
    }
}
