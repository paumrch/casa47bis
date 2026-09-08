<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Audit;

use App\Domains\Audit\AuditActor;
use App\Domains\Audit\AuditRecorder;
use App\Domains\Shared\Outbox\OutboxRecorder;
use Illuminate\Database\ConnectionInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Auditoría y bandeja de salida se niegan a trabajar fuera de una transacción.
 *
 * No es una comprobación defensiva de manual. Es la que impide el fallo más
 * desagradable de este sistema: que una auditoría o una notificación sobrevivan a un
 * cambio que se deshizo, o al revés. Ambas clases fallan ruidosamente en el momento en
 * que alguien las usa mal, en lugar de producir datos incoherentes que nadie detectará
 * hasta que haya que justificar una resolución.
 *
 * Va como prueba unitaria con la conexión suplantada porque las pruebas de
 * funcionalidad se ejecutan dentro de una transacción por diseño (RefreshDatabase), y
 * ahí el caso que interesa comprobar no puede darse nunca.
 */
final class GuardaDeTransaccionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function conexionSinTransaccion(): ConnectionInterface&MockInterface
    {
        /** @var ConnectionInterface&MockInterface $connection */
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('transactionLevel')->andReturn(0);

        return $connection;
    }

    #[Test]
    public function la_auditoria_se_niega_a_registrar_fuera_de_transaccion(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('dentro de una transacción');

        (new AuditRecorder($this->conexionSinTransaccion()))->record(
            'application.submitted',
            'application',
            null,
            AuditActor::system(),
        );
    }

    #[Test]
    public function la_bandeja_de_salida_se_niega_a_publicar_fuera_de_transaccion(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('dentro de una transacción');

        (new OutboxRecorder($this->conexionSinTransaccion()))->publish(
            'application',
            'irrelevante',
            'application.submitted',
            [],
        );
    }
}
