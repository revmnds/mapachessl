<?php

use App\Services\TelegramNotifier;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: require __DIR__.'/trusted-proxies.php');
        // Switching language is harmless; a stale token here showed "419 Page Expired" to users who left the tab open
        $middleware->validateCsrfTokens(except: ['locale']);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Only exceptions Laravel would log get here (no 404s, validation, etc.)
        $exceptions->report(function (Throwable $e) {
            app(TelegramNotifier::class)->exception($e);
        });
    })->create();
