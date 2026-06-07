<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'login',
            'register',
            'logout',
            'api/v1/auth/forgot-password',
            'api/v1/auth/reset-password',
            'api/auth/google/exchange',
            'auth/google/redirect',
            'auth/google/callback',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
        ]);
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\NoCacheHeaders::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();
