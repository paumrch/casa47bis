<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * El portal público es SIN ESTADO.
         *
         * Se retira la sesión y el reparto de cookies del grupo `web`, de modo que la
         * zona pública no instala ninguna cookie: ni de sesión, ni de token CSRF. No
         * hace falta ninguna de las dos —no hay autenticación y los formularios son GET—
         * y su ausencia tiene tres consecuencias concretas:
         *
         *  1. No hay banner de cookies porque no hay nada que consentir. En un portal de
         *     vivienda social, el mero hecho de visitarlo ya es un dato sensible.
         *  2. Las respuestas son cacheables por el CDN sin riesgo de servirle a alguien
         *     la página de otro.
         *  3. Una cabecera menos y un vector menos.
         *
         * Cuando se añada la zona de tramitación, tendrá su propio grupo CON sesión,
         * CSRF y autenticación. La separación es deliberada: lo que no necesita estado
         * no debe tenerlo.
         */
        $middleware->web(remove: [
            StartSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            AddQueuedCookiesToResponse::class,
            EncryptCookies::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
