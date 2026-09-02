<?php

namespace App\Http\Controllers\SalesCompliance;

use App\Http\Controllers\Controller;
use App\Models\SalesComplianceSite;
use App\Services\SalesCompliance\SalesComplianceDashboardService;
use App\Services\SalesCompliance\SalesComplianceInspectionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesComplianceDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        SalesComplianceDashboardService $dashboard,
        SalesComplianceInspectionService $inspections,
    ): View {
        $businessId = $this->businessId($request);
        $data = $dashboard->build($businessId, $request);

        return view('sales-compliance.hub', array_merge($data, [
            'sites' => SalesComplianceSite::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'inspectors' => $inspections->businessInspectors($businessId),
            'inspectorUsers' => $inspections->inspectorRoleUsers($businessId),
            'filters' => [
                'assignee' => (string) $request->query('assignee', ''),
                'site_id' => (string) $request->query('site_id', ''),
                'status' => (string) $request->query('status', ''),
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
                'view' => (string) $request->query('view', 'upcoming'),
            ],
        ]));
    }

    private function businessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));
        $request->user()->setActiveProcessorBusinessId($businessId);

        return $businessId;
    }
}
