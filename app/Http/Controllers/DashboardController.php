<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Services\Processor\ProcessorDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }

        $activeBusinessId = $user->activeProcessorBusinessId();
        $role = $user->processorRoleForBusiness($activeBusinessId);

        if ($activeBusinessId === null || $role === null) {
            return view('dashboard', [
                'user' => $user,
                'role' => $role,
                'activeBusiness' => null,
                'operationsDashboard' => null,
            ]);
        }

        $user->setActiveProcessorBusinessId($activeBusinessId);

        $business = Business::query()->find($activeBusinessId);
        $service = app(ProcessorDashboardService::class);
        $operationsDashboard = $service->buildForRole($activeBusinessId, $role, $user);
        $operationsDashboard['quickActions'] = $service->resolveQuickActions($operationsDashboard, $user, $activeBusinessId);

        $allRoleDashboards = null;
        if ($role === BusinessUser::ROLE_ORG_ADMIN) {
            $allRoleDashboards = [];
            foreach (BusinessUser::ROLES as $previewRole) {
                $preview = $service->buildForRole($activeBusinessId, $previewRole, $user);
                $preview['quickActions'] = $service->resolveQuickActions($preview, $user, $activeBusinessId);
                $allRoleDashboards[$previewRole] = $preview;
            }
        }

        return view('dashboard', [
            'user' => $user,
            'role' => $role,
            'activeBusiness' => $business,
            'operationsDashboard' => $operationsDashboard,
            'allRoleDashboards' => $allRoleDashboards,
        ]);
    }
}
