<?php

use App\Http\Middleware\EnsureProgramador;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'programador' => EnsureProgramador::class,
        ]);

        // Tras el edge de Railway (o Cloudflare), el esquema/host original
        // llega en cabeceras X-Forwarded-*; confiarlas hace que url(), route()
        // y la cookie de sesión se generen como https.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Si la sesión expiró (SESSION_LIFETIME corto) o el token CSRF ya no
        // cuadra, Laravel respondería 419 "Page Expired". En vez de esa página
        // se vuelve al login, que es lo que espera el usuario.
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()
                ->route('login')
                ->with('info', 'Tu sesión expiró. Vuelve a iniciar sesión.');
        });
    })->create();
