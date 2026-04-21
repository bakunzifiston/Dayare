<?php

namespace App\Http\Middleware;

use App\Models\BusinessUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    /** Route prefix => {view, create, update, delete} permission. */
    private const MODULE_PERMISSION_MAP = [
        'dashboard' => ['view' => null],
        'businesses' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'inspectors' => [
            'view' => BusinessUser::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR,
            'create' => BusinessUser::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR,
            'update' => BusinessUser::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR,
            'delete' => BusinessUser::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR,
        ],
        'businesses.facilities' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'animal-intakes' => [
            'view' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            'create' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            'update' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
            'delete' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE,
        ],
        'processor.supply-requests' => ['view' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE],
        'slaughter-plans' => [
            'view' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
            'create' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
            'update' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
            'delete' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
        ],
        'slaughter-executions' => [
            'view' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
            'create' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
            'update' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
            'delete' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER,
        ],
        'batches' => [
            'view' => BusinessUser::PERMISSION_CREATE_BATCH,
            'create' => BusinessUser::PERMISSION_CREATE_BATCH,
            'update' => BusinessUser::PERMISSION_CREATE_BATCH,
            'delete' => BusinessUser::PERMISSION_CREATE_BATCH,
        ],
        'ante-mortem-inspections' => [
            'view' => BusinessUser::PERMISSION_VIEW_INSPECTIONS,
            'create' => BusinessUser::PERMISSION_RECORD_ANTE_MORTEM,
            'update' => BusinessUser::PERMISSION_RECORD_ANTE_MORTEM,
            'delete' => BusinessUser::PERMISSION_RECORD_ANTE_MORTEM,
        ],
        'post-mortem-inspections' => [
            'view' => BusinessUser::PERMISSION_VIEW_INSPECTIONS,
            'create' => BusinessUser::PERMISSION_RECORD_POST_MORTEM,
            'update' => BusinessUser::PERMISSION_RECORD_POST_MORTEM,
            'delete' => BusinessUser::PERMISSION_RECORD_POST_MORTEM,
        ],
        'certificates' => [
            'view' => BusinessUser::PERMISSION_VIEW_CERTIFICATES,
            'create' => BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            'update' => BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
            'delete' => BusinessUser::PERMISSION_ISSUE_CERTIFICATE,
        ],
        'warehouse-storages' => ['view' => BusinessUser::PERMISSION_MONITOR_TEMPERATURE_LOGS],
        'cold-rooms' => ['view' => BusinessUser::PERMISSION_MONITOR_TEMPERATURE_LOGS],
        'cold-room-standards' => ['view' => BusinessUser::PERMISSION_MONITOR_TEMPERATURE_LOGS],
        'transport-trips' => [
            'view' => BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS,
            'create' => BusinessUser::PERMISSION_CREATE_TRANSPORT_TRIP,
            'update' => BusinessUser::PERMISSION_DISPATCH_DELIVERY,
            'delete' => BusinessUser::PERMISSION_DISPATCH_DELIVERY,
        ],
        'delivery-confirmations' => [
            'view' => BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS,
            'create' => BusinessUser::PERMISSION_CONFIRM_DELIVERY,
            'update' => BusinessUser::PERMISSION_CONFIRM_DELIVERY,
            'delete' => BusinessUser::PERMISSION_CONFIRM_DELIVERY,
        ],
        'compliance' => [
            'view' => BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS,
            'create' => BusinessUser::PERMISSION_LOG_NON_COMPLIANCE,
            'update' => BusinessUser::PERMISSION_LOG_NON_COMPLIANCE,
            'delete' => BusinessUser::PERMISSION_LOG_NON_COMPLIANCE,
        ],
        'divisions' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'crm.dashboard' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'employees' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'suppliers' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'contracts' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'clients' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'client-activities' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'demands' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'recipients' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'settings' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'species' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'units' => ['view' => BusinessUser::PERMISSION_VIEW_ALL_MODULES],
        'tenant-users' => [
            'view' => BusinessUser::PERMISSION_MANAGE_BUSINESS_USERS,
            'create' => BusinessUser::PERMISSION_ASSIGN_BUSINESS_ROLES,
            'update' => BusinessUser::PERMISSION_ASSIGN_BUSINESS_ROLES,
            'delete' => BusinessUser::PERMISSION_ASSIGN_BUSINESS_ROLES,
        ],
        'processor.business-context' => ['view' => null],
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
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (! $routeName) {
            return $next($request);
        }

        $activeBusinessId = $user->activeProcessorBusinessId();
        if ($activeBusinessId === null) {
            abort(403, __('Select a processor business first.'));
        }
        $user->setActiveProcessorBusinessId($activeBusinessId);

        $action = $this->actionForRoute($routeName);
        $requiredPermission = $this->permissionForRoute($routeName, $action);
        if ($requiredPermission === null) {
            return $next($request);
        }
        if ($user->canProcessorPermission($requiredPermission, $activeBusinessId)) {
            return $next($request);
        }
        if ($action === 'view'
            && $user->canProcessorPermission(BusinessUser::PERMISSION_VIEW_ALL_MODULES, $activeBusinessId)) {
            return $next($request);
        }

        abort(403, __('You do not have permission to access this section.'));
    }

    private function permissionForRoute(string $routeName, string $action): ?string
    {
        foreach (self::MODULE_PERMISSION_MAP as $prefix => $policies) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return $policies[$action] ?? $policies['view'] ?? null;
            }
        }

        return null;
    }

    private function actionForRoute(string $routeName): string
    {
        if (preg_match('/\.(create|store)$/', $routeName) === 1) {
            return 'create';
        }
        if (preg_match('/\.(edit|update)$/', $routeName) === 1) {
            return 'update';
        }
        if (preg_match('/\.(destroy|delete)$/', $routeName) === 1) {
            return 'delete';
        }

        return 'view';
    }

    private function isExcludedRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return $routeName === null
            || str_starts_with($routeName, 'farmer.')
            || str_starts_with($routeName, 'logistics.')
            || str_starts_with($routeName, 'super-admin.')
            || str_starts_with($routeName, 'profile.');
    }
}
