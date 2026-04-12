<?php

use App\Http\Responses\ApiJson;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * True for /api/v1 and any /api/v1/... path (Laravel's is('api/v1/*') does NOT match /api/v1 alone).
 */
$requestIsApiV1 = static fn (\Illuminate\Http\Request $request): bool => $request->is('api/v1') || $request->is('api/v1/*');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureUserIsTenant::class,
            'workspace' => \App\Http\Middleware\EnsureUserWorkspace::class,
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'tenant.permission' => \App\Http\Middleware\EnsureTenantPermission::class,
            'mobile.auth' => \App\Http\Middleware\AuthenticateMobileToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($requestIsApiV1): void {
        $exceptions->render(function (\Throwable $e, $request) use ($requestIsApiV1) {
            if (! $requestIsApiV1($request)) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiJson::fromValidationException($e);
            }

            if ($e instanceof AuthenticationException) {
                return ApiJson::failure($e->getMessage() ?: __('Unauthenticated.'), [], 401);
            }

            if ($e instanceof AuthorizationException) {
                return ApiJson::failure($e->getMessage() ?: __('Forbidden.'), [], 403);
            }

            if ($e instanceof ModelNotFoundException) {
                return ApiJson::failure(__('Not found.'), [], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage();
                // Always use short copy for 404s (Symfony/Laravel often send long "The route … could not be found" text).
                if ($status === 404) {
                    $message = __('Not found.');
                } elseif ($message === '' || $message === 'Not Found') {
                    $message = match ($status) {
                        403 => __('Forbidden.'),
                        401 => __('Unauthorized.'),
                        default => __('An error occurred.'),
                    };
                }

                return ApiJson::failure($message, [], $status);
            }

            return null;
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 403 && $request->expectsJson() === false) {
                return response()->view('errors.403', [
                    'message' => $e->getMessage() ?: __('You do not have permission to access this section.'),
                ], 403);
            }
        });
        if (config('app.debug')) {
            $exceptions->render(function (\Throwable $e, $request) use ($requestIsApiV1) {
                if ($requestIsApiV1($request)) {
                    return null;
                }
                if ($e instanceof ModelNotFoundException) {
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
