<?php

namespace App\Http\Controllers;

use App\Services\Logistics\CompanyService;
use App\Services\Logistics\LogisticsDashboardAnalyticsService;
use App\Services\Logistics\WorkspaceContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogisticsDashboardController extends Controller
{
    public function __construct(
        private WorkspaceContextService $workspaceContext,
        private CompanyService $companyService,
        private LogisticsDashboardAnalyticsService $logisticsDashboardAnalytics,
    ) {}

    public function __invoke(Request $request): View
    {
        $context = $this->workspaceContext->build($request, withOperationalData: false);
        $context['user'] = $request->user();

        $selectedCompanyId = (int) $context['selectedCompanyId'];
        $context['logisticsAnalytics'] = null;

        if ($selectedCompanyId > 0 && $request->user() !== null) {
            $this->companyService->requireAccessible($request->user(), $selectedCompanyId);
            $context['logisticsAnalytics'] = $this->logisticsDashboardAnalytics->forCompany($selectedCompanyId);
        }

        return view('logistics.dashboard', $context);
    }
}
