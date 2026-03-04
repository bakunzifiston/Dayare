<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Redirect HTTP to HTTPS when the app is configured for HTTPS (APP_URL=https://...).
     * This ensures the session cookie (Secure) is always sent on the next request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = config('app.url');
        if ($appUrl && str_starts_with($appUrl, 'https://') && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
