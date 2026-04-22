<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'isSuperAdmin') || ! method_exists($user, 'hasSuperAdminModuleAccess')) {
            return $next($request);
        }

        if (! $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->hasSuperAdminModuleAccess($module)) {
            return $next($request);
        }

        abort(403, __('You do not have access to this super admin module.'));
    }
}
