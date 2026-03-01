<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTenant
{
    /**
     * In this multi-tenant platform, each registered user is a tenant.
     * This middleware ensures the authenticated user is set as the current tenant
     * so all tenant-scoped data is isolated to this user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            app()->instance('tenant', $request->user());
        }

        return $next($request);
    }
}
