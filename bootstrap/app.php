<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Configurer les redirections pour auth/guest
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                abort(401, 'Unauthenticated');
            }
            return route('login');
        });
        $middleware->redirectUsersTo('/dashboard');

        // Exempter la route beacon-logout de la vérification CSRF
        $middleware->validateCsrfTokens(except: [
            'beacon-logout',
        ]);

        // Alias pour les middlewares personnalisés
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'permission.ownership' => \App\Http\Middleware\CheckPermissionWithOwnership::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Gérer les exceptions d'authentification pour les requêtes AJAX
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });
    })->create();
