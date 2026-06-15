<?php

namespace App\Http\Controllers;

use App\Services\SuperAdmin\SuperAdminComplianceService;
use App\Support\TenantEnvironmentScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminComplianceController extends Controller
{
    public function __construct(
        private readonly SuperAdminComplianceService $compliance,
    ) {}

    public function index(Request $request): View
    {
        TenantEnvironmentScope::setFilter(TenantEnvironmentScope::resolveFromRequest($request));

        $alert = (string) $request->query('alert', SuperAdminComplianceService::ALERT_AWAITING_CERTIFICATE);
        $meta = $this->compliance->alertMeta($alert);

        abort_if($meta === null, 404);

        $items = $this->compliance->paginatedList($alert);

        return view('super-admin.compliance.index', [
            'alert' => $alert,
            'meta' => $meta,
            'items' => $items,
            'pipelineAlerts' => $this->compliance->pipelineAlertCards(),
            'tenantEnvironmentFilter' => TenantEnvironmentScope::current(),
        ]);
    }
}
