<?php

use App\Http\Middleware\AllowWebDavMethods;
use App\Http\Middleware\SetLocaleFromCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\StripInvalidSocketId::class);

        //idioma por defecto
        $middleware->alias([
            'locale' => SetLocaleFromCookie::class,
            'webdav' => AllowWebDavMethods::class,
        ]);

        //compartir con la ruta web
        $middleware->appendToGroup('web', SetLocaleFromCookie::class);

            // Exclusiones exactas para WebDAV y OnlyOffice
    $middleware->validateCsrfTokens(except: [
        '/dav',           // Ruta raíz
        '/dav/*',         // Subrutas
        'dav/*',          // Sin slash inicial (por si acaso)
        '/onlyoffice/callback', // Callback de OnlyOffice
        '/forms/*/submit', // Permitir envíos de formularios públicos desde dominios externos
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
