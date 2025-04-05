<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
->withRouting(
    web: __DIR__.'/../routes/web.php',        // Mantiene tus rutas web
    api: __DIR__.'/../routes/api.php',        // ---> AÑADE esta línea para cargar api.php
    apiPrefix: 'api',                        // ---> AÑADE esta línea para el prefijo /api
    commands: __DIR__.'/../routes/console.php', // Mantiene tus rutas de consola
    health: '/up',                           // Mantiene tu ruta health check
)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
