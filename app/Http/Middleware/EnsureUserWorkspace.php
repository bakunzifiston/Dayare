<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserWorkspace
{
    /**
     * Restrict routes to users whose workspace (primary business type) matches.
     *
     * @param  string  $workspace  One of: farmer, processor, logistics
     */
    public function handle(Request $request, Closure $next, string $workspace): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $allowed = match ($workspace) {
            Business::TYPE_FARMER => [Business::TYPE_FARMER],
            Business::TYPE_LOGISTICS => [Business::TYPE_LOGISTICS],
            Business::TYPE_PROCESSOR => [Business::TYPE_PROCESSOR],
            default => [],
        };

        if ($allowed === [] || ! in_array($user->tenantWorkspaceType(), $allowed, true)) {
            abort(403, __('This area is not available for your account type.'));
        }

        return $next($request);
    }
}
