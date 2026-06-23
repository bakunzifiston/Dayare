<?php

namespace App\Http\Controllers;

use App\Models\Business;
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
        $operationsDashboard = $service->buildForRole($activeBusinessId, $role, $user, $request);

        return view('dashboard', [
            'user' => $user,
            'role' => $role,
            'activeBusiness' => $business,
            'operationsDashboard' => $operationsDashboard,
        ]);
    }
}
