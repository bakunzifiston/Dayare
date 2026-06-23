<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\SuperAdmin\SuperAdminComplianceService;
use App\Services\SuperAdmin\SuperAdminSlaughterDashboardService;
use App\Support\TenantEnvironmentScope;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
{
    public function __construct(
        private readonly SuperAdminComplianceService $complianceService,
        private readonly SuperAdminSlaughterDashboardService $slaughterDashboard,
    ) {}

    public function index(Request $request): View
    {
        TenantEnvironmentScope::setFilter(User::TENANT_ENVIRONMENT_LIVE);

        $filters = $this->slaughterDashboard->resolveHubFilters($request);

        $complianceSummary = $this->complianceService->summaryBar();
        $administrativeAlerts = $this->complianceService->administrativeAlertCards();
        $workspaceKpis = array_merge($this->workspaceKpis(), [
            'active_facilities' => $complianceSummary['active_facilities'],
        ]);
        $speciesSlaughtered = $this->slaughterDashboard->speciesSlaughteredCounts($filters);
        $facilitySlaughterRows = $this->slaughterDashboard->facilitySlaughterRows($filters);
        $charts = [
            'species_animal_intake_trend' => $this->slaughterDashboard->chartSpeciesAnimalIntakeTrend($filters),
            'species_slaughter_pie' => $this->slaughterDashboard->chartSpeciesSlaughterPie($speciesSlaughtered),
        ];

        return view('super-admin.dashboard', compact(
            'workspaceKpis',
            'speciesSlaughtered',
            'facilitySlaughterRows',
            'administrativeAlerts',
            'filters',
            'charts',
        ));
    }

    private function workspaceKpis(): array
    {
        $businessQuery = TenantEnvironmentScope::applyToBusinesses(Business::query());

        return [
            'tenants' => (int) (clone $businessQuery)->distinct('user_id')->count('user_id'),
            'businesses' => (clone $businessQuery)->count(),
            'facilities' => TenantEnvironmentScope::applyToFacilities(Facility::query())->count(),
            'users' => User::count(),
        ];
    }
}
