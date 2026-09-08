<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Shared\Outbox\OutboxDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // El dominio depende de la interfaz de conexión, no de la fachada DB ni de un
        // modelo Eloquent concreto. Es lo que permite que AuditRecorder y OutboxRecorder
        // se instancien en una prueba con una conexión cualquiera, y lo que deja claro
        // en la firma de cada clase que necesita una transacción.
        $this->app->bind(ConnectionInterface::class, static fn (): ConnectionInterface => DB::connection());

        $this->app->singleton(OutboxDispatcher::class, static function ($app): OutboxDispatcher {
            $dispatcher = new OutboxDispatcher(
                $app->make(ConnectionInterface::class),
                $app->make(LoggerInterface::class),
            );

            // Los manejadores se registran aquí, en un único sitio legible. Cuando haya
            // adaptadores reales de notificación o del sistema económico, se añaden a
            // esta lista y no hay que tocar el despachador.
            foreach (config('outbox.handlers', []) as $handlerClass) {
                $dispatcher->register($app->make($handlerClass));
            }

            return $dispatcher;
        });
    }

    public function boot(): void
    {
        //
    }
}
