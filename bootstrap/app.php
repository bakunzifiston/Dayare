<?php

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
        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureUserIsTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('Page expired. Please refresh and try again.')], 419);
            }
            $refreshUrl = $request->headers->get('referer');
            if (!$refreshUrl || !\Illuminate\Support\Str::startsWith($refreshUrl, [config('app.url'), $request->getSchemeAndHttpHost()])) {
                $refreshUrl = url('/');
            }
            return response()->view('errors.419', ['refreshUrl' => $refreshUrl], 419);
        });
    })->create();
