<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Documents\Contracts\MalwareScanner;
use App\Domains\Documents\Scanning\AlwaysCleanScanner;
use App\Domains\Shared\Outbox\OutboxDispatcher;
use App\Integrations\Contracts\DataVerificationGateway;
use App\Integrations\Contracts\DocumentStorageGateway;
use App\Integrations\Contracts\NotificationGateway;
use App\Integrations\Storage\FilesystemDocumentStorage;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        // Los puertos del dominio se resuelven a adaptadores concretos según la
        // configuración. El dominio nunca nombra a un proveedor: nombra a su puerto.
        $this->app->bind(
            DataVerificationGateway::class,
            static fn ($app): DataVerificationGateway => $app->make((string) config('integrations.data_verification')),
        );

        $this->app->bind(
            NotificationGateway::class,
            static fn ($app): NotificationGateway => $app->make((string) config('integrations.notifications')),
        );

        $this->app->bind(
            DocumentStorageGateway::class,
            static fn ($app): DocumentStorageGateway => new FilesystemDocumentStorage(
                Storage::disk((string) config('filesystems.default')),
            ),
        );

        // En desarrollo, un escáner que da todo por limpio. En producción va contra un
        // antivirus real, y hasta que un documento no está analizado no puede
        // descargarse ni darse por válido: el estado «pendiente» es una barrera, no una
        // etiqueta informativa.
        $this->app->bind(MalwareScanner::class, AlwaysCleanScanner::class);

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
