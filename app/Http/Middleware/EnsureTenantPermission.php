<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    /** Route name (or prefix) => permission required. More specific prefixes must appear first. */
    private const ROUTE_PERMISSION_MAP = [
        'dashboard' => null,
        'profile' => null,

        'businesses.facilities' => 'manage facilities',
        'businesses' => 'manage businesses',
        'inspectors' => 'manage inspectors',
        'animal-intakes' => 'manage animal intakes',
        'slaughter-plans' => 'manage slaughter plans',
        'slaughter-executions' => 'manage slaughter executions',
        'batches' => 'manage batches',
        'ante-mortem-inspections' => 'manage ante-mortem',
        'post-mortem-inspections' => 'manage post-mortem',
        'certificates' => 'manage certificates',
        'warehouse-storages' => 'manage warehouse',
        'transport-trips' => 'manage transport',
        'delivery-confirmations' => 'manage delivery confirmations',
        'compliance' => 'view compliance',
        'divisions' => 'view divisions',

        'crm.dashboard' => 'view crm',
        'employees' => 'manage employees',
        'suppliers' => 'manage suppliers',
        'contracts' => 'manage contracts',
        'clients' => 'manage clients',
        'client-activities' => 'manage clients',
        'demands' => 'manage demands',
        'recipients' => 'view recipients',

        'settings' => 'manage settings',
        'species' => 'manage species',
        'units' => 'manage units',

        'tenant-users' => 'manage tenant users',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }
        // Business owners (users who own at least one business) can access all tenant modules.
        if (method_exists($user, 'canManageTenantUsers') && $user->canManageTenantUsers()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (! $routeName) {
            return $next($request);
        }

        $requiredPermission = $this->permissionForRoute($routeName);
        if ($requiredPermission === null) {
            return $next($request);
        }
        if ($user->can($requiredPermission)) {
            return $next($request);
        }

        abort(403, __('You do not have permission to access this section.'));
    }

    private function permissionForRoute(string $routeName): ?string
    {
        foreach (self::ROUTE_PERMISSION_MAP as $prefix => $permission) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix . '.')) {
                return $permission;
            }
        }
        return null;
    }
}
