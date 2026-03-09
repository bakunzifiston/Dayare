<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureUserIsTenant::class,
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        if (config('app.debug')) {
            $exceptions->render(function (Throwable $e, $request) {
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->view('errors.debug-404', [
                        'title' => 'Model not found (404)',
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ], 404);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->view('errors.debug-404', [
                        'title' => 'Page not found (404)',
                        'message' => $e->getMessage() ?: 'The requested URL was not found.',
                        'trace' => $e->getTraceAsString(),
                    ], 404);
                }
            });
        }
    })
    ->create();
