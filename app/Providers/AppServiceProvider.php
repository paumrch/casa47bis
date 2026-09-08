<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Shared\Outbox\OutboxDispatcher;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
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
        // Versión del CSS derivada de su fecha de modificación: invalida la caché al
        // desplegar sin necesitar una cadena de compilación ni un manifiesto de assets.
        View::share('cssVersion', $this->cssVersion());

        Carbon::setLocale('es');
    }

    private function cssVersion(): string
    {
        $path = public_path('assets/portal.css');

        return file_exists($path) ? (string) filemtime($path) : '0';
    }
}
